<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\Metrics\Http\Requests\MetricDataRequest;
use Dashworthy\Visualizations\Traits\ParsesVisualizationInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

class VisualizationData
{
    use Macroable, ParsesVisualizationInput;

    /** @var Collection<int, FilterSetData> */
    public Collection $filterSets;

    /** @var Collection<int, SortData> */
    public Collection $sorts;

    public function __construct()
    {
        $this->filterSets = collect();
        $this->sorts = collect();
    }

    public static function fromDataGridRequest(DataGridDataRequest $request): self
    {
        $data = new self;
        $data->filterSets = $data->parseFilterSets($request->input('filter_sets', []));
        $data->sorts = $data->parseSorts($request->input('sorts', []));

        return $data;
    }

    public static function fromChartRequest(ChartDataRequest $request): self
    {
        $data = new self;
        $data->filterSets = $data->parseFilterSets($request->input('filter_sets', []));
        $data->sorts = $data->parseSorts($request->input('sorts', []));

        return $data;
    }

    public static function fromMetricRequest(MetricDataRequest $request): self
    {
        $data = new self;
        $data->filterSets = $data->parseFilterSets($request->input('filter_sets', []));
        $data->sorts = collect();

        return $data;
    }

    public function addFilterSet(FilterSetOperator $filterSetOperator, \Closure $closure): self
    {
        $builder = new FilterBuilder;
        $closure($builder);

        $this->filterSets->push(
            new FilterSetData(
                $builder->getFilters(),
                $filterSetOperator
            )
        );

        return $this;
    }

    public function addAndFilterSet(\Closure $closure): self
    {
        return $this->addFilterSet(FilterSetOperator::AND, $closure);
    }

    public function addOrFilterSet(\Closure $closure): self
    {
        return $this->addFilterSet(FilterSetOperator::OR, $closure);
    }

    public function addSort(string $field, SortOperator $sortOperator): self
    {
        $this->sorts->push(new SortData($field, $sortOperator));

        return $this;
    }

    public function addSortAsc(string $field): self
    {
        return $this->addSort($field, SortOperator::ASC);
    }

    public function addSortDesc(string $field): self
    {
        return $this->addSort($field, SortOperator::DESC);
    }
}
