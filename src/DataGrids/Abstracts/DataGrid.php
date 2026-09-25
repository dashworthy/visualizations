<?php

namespace Dashworthy\Visualizations\DataGrids\Abstracts;

use Dashworthy\Visualizations\Abstracts\Visualization;
use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;
use Dashworthy\Visualizations\Data\FetchedData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridSchemaRequest;
use Dashworthy\Visualizations\Enums\VisualizationType;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

abstract class DataGrid extends Visualization
{
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
            'columns' => $this->getColumns()->map->toArray(),
            'default_sorts' => $this->getDefaultSorts()->map->toArray(),
        ];
    }

    protected function getPrimaryVisualizables(): Collection
    {
        return $this->getColumns();
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
}
