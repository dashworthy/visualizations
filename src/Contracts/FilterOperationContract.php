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

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder;
}
