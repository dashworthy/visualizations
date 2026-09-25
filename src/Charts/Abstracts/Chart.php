<?php

namespace Dashworthy\Visualizations\Charts\Abstracts;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Abstracts\Visualization;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartSchemaRequest;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\Charts\Labels\NullLabel;
use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;
use Dashworthy\Visualizations\Data\FetchedData;
use Dashworthy\Visualizations\Enums\VisualizationType;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

abstract class Chart extends Visualization
{
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
     * Prefix for the route name.  Example: 'charts' would result in 'charts.revenue'
     */
    public function getRoutePrefix(): string
    {
        return 'charts';
    }

    public function getVisualizationType(): DefinesVisualizationType
    {
        return VisualizationType::Chart;
    }

    /**
     * Handles the API request to retrieve the chart data.
     *
     * @throws Exception
     */
    public function handleData(ChartDataRequest $request): JsonResponse
    {
        return $this->respondWithData($request);
    }

    /**
     * Handles building the schema for consumption by the front-end.
     */
    public function handleSchema(ChartSchemaRequest $request): JsonResponse
    {
        return $this->respondWithSchema();
    }

    protected function getClassSuffix(): string
    {
        return 'Chart';
    }

    protected function getSchemaBody(): array
    {
        return [
            'label' => $this->getLabel()->toArray(),
            'datasets' => $this->getDatasets()->map->toArray(),
        ];
    }

    /**
     * The label, then the datasets. A NullLabel selects nothing, so it is left out.
     */
    protected function getPrimaryVisualizables(): Collection
    {
        $label = $this->getLabel();

        /** @var Collection<int, Visualizable> $visualizables */
        $visualizables = $label instanceof NullLabel
            ? $this->getDatasets()
            : collect([$label])->concat($this->getDatasets());

        return $visualizables;
    }

    protected function fetchData(Builder $query, FormRequest $request): FetchedData
    {
        $results = $query->get();

        return new FetchedData($results, $results->count());
    }
}
