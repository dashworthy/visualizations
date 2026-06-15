<?php

namespace Dashworthy\Visualizations\FilterOperations\Text;

use Illuminate\Database\Query\Builder;
use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;

class DoesNotContain extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::STRING_DOES_NOT_CONTAIN;
    }

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        $col = $visualizable->getFilterWith();
        $expression = '('.$col.' NOT LIKE ? OR '.$col.' IS NULL)';
        $bindings = [...$visualizable->getFilterWithBindings(), '%'.$filterData->value.'%'];
        $method = $this->getQueryMethod($visualizable, $filterOperator);
        $query->$method($expression, $bindings);

        return $query;
    }
}
