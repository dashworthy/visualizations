<?php

namespace Dashworthy\Visualizations\Metrics\Abstracts;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Metrics\Http\Requests\MetricDataRequest;
use Dashworthy\Visualizations\Metrics\Http\Requests\MetricSchemaRequest;
use Dashworthy\Visualizations\Metrics\Value;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Dashworthy\Visualizations\Traits\Cacheable;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

abstract class Metric implements VisualizationContract
{
    use Cacheable;

    final public function __construct() {}

    /**
     * Builds the definitional structure of the metric for front-end consumption.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $metric = new static;

        return [
            'visualization_key' => $metric->getVisualizationKey(),
            'value' => $metric->getValue()->toArray(),
            'floating_filters' => $metric->getFloatingFilters()->map->toArray(),
        ];
    }

    /**
     * Used to define the scalar aggregate value for this metric.
     */
    abstract public function getValue(): Value;

    /**
     * The base query that the metric data is built from.
     */
    abstract public function getQuery(): Builder;

    /**
     * Defines optional floating filters for the metric. Floating filters allow filtering on fields that are not
     * part of the metric's visible data, such as filtering by a related entity.
     *
     * @return Collection<int, FloatingFilter>
     */
    public function getFloatingFilters(): Collection
    {
        return collect();
    }

    /**
     * Prefix for the route name. Example: 'metrics' would result in 'metrics.revenues'
     */
    public function getRoutePrefix(): string
    {
        return 'metrics';
    }

    /**
     * Automatically generates the route name used as Laravel's named route.
     */
    public function getRouteName(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before('Metric')
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
            ->before('Metric')
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
     * Assembles all visualizables (value and floating filters) into a single collection for query generation.
     * Uses concat() rather than merge() to guarantee no items are dropped regardless of collection key types.
     *
     * @return Collection<int, Visualizable>
     */
    public function getVisualizables(): Collection
    {
        /** @var Collection<int, Visualizable> $visualizables */
        $visualizables = collect([$this->getValue()])->concat($this->getFloatingFilters());

        return $visualizables;
    }

    /**
     * Handles the API request to retrieve the metric data.
     *
     * @throws Exception
     */
    public function handleData(MetricDataRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        if (method_exists($this, 'getPermissionName')) {
            Gate::authorize($this->getPermissionName());
        }

        $query = GenerateVisualizationQuery::make()->handle(
            $this->getQuery(),
            $this->getVisualizables(),
            VisualizationData::fromMetricRequest($request)
        );

        $sql = $query->toRawSql();
        $result = $query->first();
        $field = $this->getValue()->getField();

        event(new VisualizationQueryExecuted(
            visualizationKey: $this->getVisualizationKey(),
            visualizationType: 'metric',
            sql: $sql,
            durationMs: (microtime(true) - $startedAt) * 1000,
            rowCount: $result !== null ? 1 : 0,
        ));

        return response()->json([
            'value' => $result->{$field},
        ]);
    }

    /**
     * Handles building the schema for consumption by the front-end.
     */
    public function handleSchema(MetricSchemaRequest $request): JsonResponse
    {
        if (method_exists($this, 'getPermissionName')) {
            Gate::authorize($this->getPermissionName());
        }

        return response()->json(static::schema());
    }
}
