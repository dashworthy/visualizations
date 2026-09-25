<?php

namespace Dashworthy\Visualizations\DataGrids\Abstracts;

use Dashworthy\Visualizations\Abstracts\Visualization;
use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;
use Dashworthy\Visualizations\Data\FetchedData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridSchemaRequest;
use Dashworthy\Visualizations\Enums\VisualizationType;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

abstract class DataGrid extends Visualization
{
    /** @var Collection<int, Column>|null */
    private ?Collection $columns = null;

    /**
     * Used to define the columns that will be available in the data grid
     *
     * @return Collection<int, Column>
     */
    abstract public function getColumns(): Collection;

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

    public function getVisualizationType(): DefinesVisualizationType
    {
        return VisualizationType::DataGrid;
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
        return $this->respondWithData($request);
    }

    /**
     * Fills this grid's hydrated columns on an already-fetched page.
     *
     * The one step an export must call to ship the same values the grid displays.
     *
     * @param  Collection<int, \stdClass>  $rows
     * @return Collection<int, \stdClass>
     *
     * @throws Exception
     */
    public function hydrate(Collection $rows): Collection
    {
        $this->columns()
            ->whereInstanceOf(HydratedColumn::class)
            ->each(fn (HydratedColumn $column) => $column->hydrate($rows, $this->keyFieldFor($column)));

        return $rows;
    }

    /**
     * Handles building the schema for consumption by the front-end.
     */
    public function handleSchema(DataGridSchemaRequest $request): JsonResponse
    {
        return $this->respondWithSchema();
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

    protected function getClassSuffix(): string
    {
        return 'DataGrid';
    }

    protected function getSchemaBody(): array
    {
        return [
            'columns' => $this->columns()->map->toArray(),
            'default_sorts' => $this->getDefaultSorts()->map->toArray(),
        ];
    }

    protected function getPrimaryVisualizables(): Collection
    {
        return $this->columns();
    }

    /**
     * Fetches a first/last window when the request asks for one, otherwise a page.
     */
    protected function fetchData(Builder $query, FormRequest $request): FetchedData
    {
        if ($request->has('first') && $request->has('last')) {
            $first = $request->input('first');
            $last = $request->input('last');
            $results = $query->take($last - $first)->offset($first)->get();

            return new FetchedData(['first' => $first, 'last' => $last, 'data' => $results], $results->count());
        }

        $page = $query->paginate($request->input('per_page', 250));

        return new FetchedData($page, $page->count());
    }

    /**
     * Hydrates whichever branch fetchData() took. Runs after the query event, so durationMs times the
     * main query alone.
     *
     * @throws Exception
     */
    protected function finalizePayload(FetchedData $fetched): mixed
    {
        $payload = $fetched->payload;

        if ($payload instanceof AbstractPaginator) {
            return $payload->setCollection($this->hydrate($payload->getCollection()));
        }

        return [...$payload, 'data' => $this->hydrate($payload['data'])];
    }

    /**
     * Memoised: a data request asks twice, once to build the statement and once to hydrate, and both
     * must see the same column objects — matching a hydrator's key against a second, separately built
     * graph would trust getColumns() to be pure.
     *
     * @return Collection<int, Column>
     */
    private function columns(): Collection
    {
        return $this->columns ??= $this->getColumns();
    }

    /**
     * The payload field ('column_ID') behind a hydrator's declared key ('ID').
     *
     * Only a column the statement selects can key a row; a hydrated column holds no value yet.
     * Floating filters are never selected either, and are not columns, so they cannot match here.
     *
     * @throws Exception
     */
    private function keyFieldFor(HydratedColumn $column): string
    {
        $hydrator = $column->getHydrator();
        $declaredField = $hydrator->keyedBy();

        $keyColumn = $this->columns()->first(
            fn (Column $candidate): bool => $candidate->hasExpression()
                && $candidate->getField() === $candidate->getFieldPrefix().$declaredField
        );

        if (! $keyColumn instanceof Column) {
            throw new Exception(sprintf(
                "%s keys on '%s', which is not a column on this grid.",
                $hydrator::class,
                $declaredField,
            ));
        }

        return $keyColumn->getField();
    }
}
