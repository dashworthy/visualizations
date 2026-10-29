<?php

namespace Dashworthy\Visualizations\Contracts;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;

interface FilterOperationContract
{
    public function canHandle(FilterOperator $filterOperator): bool;

    /**
     * Validation rules for a filter this operation handles, keyed from the filter: `value`, and `value.*` for each
     * item of a list. They decide which values reach handle(), so they must admit only what it can bind.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array;

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder;
}
