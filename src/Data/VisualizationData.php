<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\Traits\ParsesVisualizationInput;
use Illuminate\Http\Request;
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

    /**
     * Parses the filter sets and sorts from a data request.
     */
    public static function fromRequest(Request $request): self
    {
        $data = new self;
        $data->filterSets = $data->parseFilterSets($request->input('filter_sets', []));
        $data->sorts = $data->parseSorts($request->input('sorts', []));

        return $data;
    }

    public static function fromDataGridRequest(DataGridDataRequest $request): self
    {
        return self::fromRequest($request);
    }

    public static function fromChartRequest(ChartDataRequest $request): self
    {
        return self::fromRequest($request);
    }

    /**
     * Parses only the filter sets. A metric's request does not validate sorts, so they are never read.
     */
    public static function fromMetricRequest(Request $request): self
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
