<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\Funnels;

use Dashworthy\Visualizations\Abstracts\Visualization;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartSchemaRequest;
use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;
use Dashworthy\Visualizations\Data\FetchedData;
use Dashworthy\Visualizations\Metrics\Value;
use Dashworthy\Visualizations\Tests\Fixtures\CustomVisualizationType;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A kind this package does not ship, built the way an application would:
 * extend `Visualization` and fill in only what makes a funnel a funnel.
 */
class SalesFunnel extends Visualization
{
    public function getVisualizationType(): DefinesVisualizationType
    {
        return CustomVisualizationType::Funnel;
    }

    public function getRoutePrefix(): string
    {
        return 'funnels';
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }

    public function handleData(ChartDataRequest $request): JsonResponse
    {
        return $this->respondWithData($request);
    }

    public function handleSchema(ChartSchemaRequest $request): JsonResponse
    {
        return $this->respondWithSchema();
    }

    protected function getClassSuffix(): string
    {
        return 'Funnel';
    }

    protected function getSchemaBody(): array
    {
        return ['stages' => $this->getStages()->map->toArray()];
    }

    protected function getPrimaryVisualizables(): Collection
    {
        return $this->getStages();
    }

    protected function fetchData(Builder $query, FormRequest $request): FetchedData
    {
        $rows = $query->get();

        return new FetchedData($rows, $rows->count());
    }

    /** @return Collection<int, Value> */
    private function getStages(): Collection
    {
        return collect([Value::make('count(*)', 'visitors')]);
    }
}
