<?php

namespace Dashworthy\Visualizations\FilterOperations\Equality;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;

class LessThan extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::LESS_THAN;
    }

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        $expression = $visualizable->getFilterWith().' < ?';
        $bindings = [...$visualizable->getFilterWithBindings(), $filterData->value];
        $method = $this->getQueryMethod($visualizable, $filterOperator);
        $query->$method($expression, $bindings);

        return $query;
    }
}
