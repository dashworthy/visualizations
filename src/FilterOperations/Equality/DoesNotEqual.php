<?php

namespace Dashworthy\Visualizations\FilterOperations\Equality;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;

class DoesNotEqual extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::NOT_EQUALS;
    }

    private function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        if ($filterData->value === null) {
            return $visualizable->getFilterWith().' IS NOT NULL';
        }

        return $visualizable->getFilterWith().' != ?';
    }

    /**
     * @return array<int, mixed>
     */
    private function buildBindings(Visualizable $visualizable, FilterData $filterData): array
    {
        if ($filterData->value === null) {
            return $visualizable->getFilterWithBindings();
        }

        return [...$visualizable->getFilterWithBindings(), $filterData->value];
    }

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        $expression = $this->buildExpression($visualizable, $filterData);
        $bindings = $this->buildBindings($visualizable, $filterData);
        $method = $this->getQueryMethod($visualizable, $filterOperator);
        $query->$method($expression, $bindings);

        return $query;
    }
}
