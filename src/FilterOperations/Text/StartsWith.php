<?php

namespace Dashworthy\Visualizations\FilterOperations\Text;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;

class StartsWith extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::STRING_STARTS_WITH;
    }

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        $expression = $visualizable->getFilterWith().' LIKE ?';
        $bindings = [...$visualizable->getFilterWithBindings(), $filterData->value.'%'];
        $method = $this->getQueryMethod($visualizable, $filterOperator);
        $query->$method($expression, $bindings);

        return $query;
    }
}
