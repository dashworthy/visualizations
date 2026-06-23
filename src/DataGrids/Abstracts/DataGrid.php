<?php

namespace Dashworthy\Visualizations\DataGrids\Abstracts;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridSchemaRequest;
use Dashworthy\Visualizations\DataGrids\Traits\HandlesDataGridActions;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

abstract class DataGrid implements VisualizationContract
{
    use HandlesDataGridActions;

    final public function __construct() {}

    /**
     * Builds the definitional structure of the datagrid
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $dataGrid = new static;

        return [
            'visualization_key' => $dataGrid->getVisualizationKey(),
            'columns' => $dataGrid->getColumns()->map->toArray(),
            'floating_filters' => $dataGrid->getFloatingFilters()->map->toArray(),
            'default_sorts' => $dataGrid->getDefaultSorts()->map->toArray(),
            'bulk_actions' => $dataGrid->getBulkActions()->map->toArray(),
            'inline_actions' => $dataGrid->getInlineActions()->map->toArray(),
        ];
    }

    /**
     * Used to define the columns that will be available in the data grid
     *
     * @return Collection<int, Column>
     */
    abstract public function getColumns(): Collection;

    /**
     * Used to define the floating filters that will be available in the data grid
     *
     * @return Collection<int, FloatingFilter>
     */
    public function getFloatingFilters(): Collection
    {
        return collect();
    }

    /**
     * Assembles all visualizables (columns and floating filters) into a single collection for query generation.
     * Uses concat() rather than merge() to guarantee no items are dropped regardless of collection key types.
     *
     * @return Collection<int, Visualizable>
     */
    public function getVisualizables(): Collection
    {
        /** @var Collection<int, Visualizable> $visualizables */
        $visualizables = $this->getColumns()->concat($this->getFloatingFilters());

        return $visualizables;
    }

    /**
     * The base query for the data grid.
     */
    abstract public function getQuery(): Builder;

    /**
     * Prefix for the route name.  Example: 'grids' would result in 'grids.users'
     */
    public function getRoutePrefix(): string
    {
        return 'grids';
    }

    /**
     * Automatically generates the route name which will be used as Laravel's named route.
     */
    public function getRouteName(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before('DataGrid')
            ->snake('-')
            ->plural()
            ->prepend($this->getRoutePrefix().'.')
            ->toString();
    }

    /**
     * Automatically generates the route path which will be used as the URL path.
     */
    public function getRoutePath(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before('DataGrid')
            ->plural()
            ->snake('-')
            ->prepend('/')
            ->prepend($this->getRoutePrefix())
            ->toString();
    }

    /**
     * The relative URL path for an action's dedicated route.
     *
     * @param  string  $type  'inline' or 'bulk'
     */
    public function actionPath(string $type, Action $action): string
    {
        return $this->getRoutePath().'/actions/'.$type.'/'.$action->getSlug();
    }

    public function getVisualizationKey(): string
    {
        return $this->getRouteName();
    }

    /**
     * Handles the API request to get the data for the grid.
     *
     * @param  DataGridDataRequest  $request  The request instance.
     *
     * @throws Exception
     */
    public function handleData(DataGridDataRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        if (method_exists($this, 'getPermissionName')) {
            Gate::authorize($this->getPermissionName());
        }

        $query = GenerateVisualizationQuery::make()->handle(
            $this->getQuery(),
            $this->getVisualizables(),
            VisualizationData::fromDataGridRequest($request)
        );

        $sql = $query->toRawSql();

        if ($request->has('first') && $request->has('last')) {
            $first = $request->input('first');
            $last = $request->input('last');
            $results = $query->take($last - $first)->offset($first)->get();

            event(new VisualizationQueryExecuted(
                visualizationKey: $this->getVisualizationKey(),
                visualizationType: 'datagrid',
                sql: $sql,
                durationMs: (microtime(true) - $startedAt) * 1000,
                rowCount: $results->count(),
            ));

            return response()->json(['first' => $first, 'last' => $last, 'data' => $results]);
        }

        $data = $query->paginate($request->input('per_page', 250));

        event(new VisualizationQueryExecuted(
            visualizationKey: $this->getVisualizationKey(),
            visualizationType: 'datagrid',
            sql: $sql,
            durationMs: (microtime(true) - $startedAt) * 1000,
            rowCount: $data->count(),
        ));

        return response()->json($data);
    }

    /**
     * Handles building the schema for consumption by the front-end.
     */
    public function handleSchema(DataGridSchemaRequest $request): JsonResponse
    {
        if (method_exists($this, 'getPermissionName')) {
            Gate::authorize($this->getPermissionName());
        }

        return response()->json(static::schema());
    }

    /**
     * Defines a collection of initial sorts that will be communicated to the front-end.
     *
     * @return Collection<int, SortData>
     */
    public function getDefaultSorts(): Collection
    {
        return collect();
    }
}
