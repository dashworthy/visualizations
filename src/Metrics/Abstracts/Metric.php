<?php

namespace Dashworthy\Visualizations\Metrics\Abstracts;

use Dashworthy\Visualizations\Abstracts\Visualization;
use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;
use Dashworthy\Visualizations\Data\FetchedData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Enums\VisualizationType;
use Dashworthy\Visualizations\Metrics\Http\Requests\MetricDataRequest;
use Dashworthy\Visualizations\Metrics\Http\Requests\MetricSchemaRequest;
use Dashworthy\Visualizations\Metrics\Value;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

abstract class Metric extends Visualization
{
    /**
     * Used to define the scalar aggregate value for this metric.
     */
    abstract public function getValue(): Value;

    /**
     * The base query that the metric data is built from.
     */
    abstract public function getQuery(): Builder;

    /**
     * Prefix for the route name. Example: 'metrics' would result in 'metrics.revenues'
     */
    public function getRoutePrefix(): string
    {
        return 'metrics';
    }

    public function getVisualizationType(): DefinesVisualizationType
    {
        return VisualizationType::Metric;
    }

    /**
     * Handles the API request to retrieve the metric data.
     *
     * @throws Exception
     */
    public function handleData(MetricDataRequest $request): JsonResponse
    {
        return $this->respondWithData($request);
    }

    /**
     * Handles building the schema for consumption by the front-end.
     */
    public function handleSchema(MetricSchemaRequest $request): JsonResponse
    {
        return $this->respondWithSchema();
    }

    protected function getClassSuffix(): string
    {
        return 'Metric';
    }

    protected function getSchemaBody(): array
    {
        return ['value' => $this->getValue()->toArray()];
    }

    protected function getPrimaryVisualizables(): Collection
    {
        return collect([$this->getValue()]);
    }

    /**
     * A metric is a single aggregate, so it takes filters but no sorts.
     */
    protected function getVisualizationData(FormRequest $request): VisualizationData
    {
        return VisualizationData::fromMetricRequest($request);
    }

    protected function fetchData(Builder $query, FormRequest $request): FetchedData
    {
        $result = $query->first();

        return new FetchedData(
            ['value' => $result->{$this->getValue()->getField()}],
            $result !== null ? 1 : 0,
        );
    }
}
