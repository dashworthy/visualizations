<?php

namespace Dashworthy\Visualizations\Charts\Abstracts;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartSchemaRequest;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\Charts\Labels\NullLabel;
use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Dashworthy\Visualizations\Traits\Cacheable;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

abstract class Chart implements VisualizationContract
{
    use Cacheable;

    final public function __construct() {}

    /**
     * Builds the definitional structure of the chart for front-end consumption.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $chart = new static;

        return [
            'visualization_key' => $chart->getVisualizationKey(),
            'label' => $chart->getLabel()->toArray(),
            'datasets' => $chart->getDatasets()->map->toArray(),
            'floating_filters' => $chart->getFloatingFilters()->map->toArray(),
        ];
    }

    /**
     * Used to define the label (grouping/axis field) for the chart.
     */
    abstract public function getLabel(): Label;

    /**
     * Used to define the datasets (series) that will be available in the chart.
     *
     * @return Collection<int, Dataset>
     */
    abstract public function getDatasets(): Collection;

    /**
     * The base query that the chart data is built from.
     */
    abstract public function getQuery(): Builder;

    /**
     * Defines optional floating filters for the chart. Floating filters allow filtering on fields that are not
     * part of the chart's visible data (label or datasets), such as filtering by a related entity.
     *
     * @return Collection<int, FloatingFilter>
     */
    public function getFloatingFilters(): Collection
    {
        return collect();
    }

    /**
     * Prefix for the route name.  Example: 'charts' would result in 'charts.revenue'
     */
    public function getRoutePrefix(): string
    {
        return 'charts';
    }

    /**
     * Automatically generates the route name used as Laravel's named route.
     */
    public function getRouteName(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before('Chart')
            ->snake('-')
            ->plural()
            ->prepend($this->getRoutePrefix().'.')
            ->toString();
    }

    /**
     * Automatically generates the route path used as the URL path.
     */
    public function getRoutePath(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before('Chart')
            ->plural()
            ->snake('-')
            ->prepend('/')
            ->prepend($this->getRoutePrefix())
            ->toString();
    }

    public function getVisualizationKey(): string
    {
        return $this->getRouteName();
    }

    /**
     * Assembles all visualizables (label, datasets, floating filters) into a single collection for query generation.
     * Uses concat() rather than merge() to guarantee no items are dropped regardless of collection key types.
     *
     * @return Collection<int, Visualizable>
     */
    public function getVisualizables(): Collection
    {
        $label = $this->getLabel();
        $datasets = $this->getDatasets();
        $floatingFilters = $this->getFloatingFilters();

        /** @var Collection<int, Visualizable> $visualizables */
        $visualizables = $label instanceof NullLabel
            ? $datasets->concat($floatingFilters)
            : collect([$label])->concat($datasets)->concat($floatingFilters);

        return $visualizables;
    }

    /**
     * Handles the API request to retrieve the chart data.
     *
     * @throws Exception
     */
    public function handleData(ChartDataRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        if (method_exists($this, 'getPermissionName')) {
            Gate::authorize($this->getPermissionName());
        }

        $query = GenerateVisualizationQuery::make()->handle(
            $this->getQuery(),
            $this->getVisualizables(),
            VisualizationData::fromChartRequest($request)
        );

        $sql = $query->toRawSql();
        $results = $query->get();

        event(new VisualizationQueryExecuted(
            visualizationKey: $this->getVisualizationKey(),
            visualizationType: 'chart',
            sql: $sql,
            durationMs: (microtime(true) - $startedAt) * 1000,
            rowCount: $results->count(),
        ));

        return response()->json($results);
    }

    /**
     * Handles building the schema for consumption by the front-end.
     */
    public function handleSchema(ChartSchemaRequest $request): JsonResponse
    {
        if (method_exists($this, 'getPermissionName')) {
            Gate::authorize($this->getPermissionName());
        }

        return response()->json(static::schema());
    }
}
