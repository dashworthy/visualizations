<?php

namespace Dashworthy\Visualizations\FilterOperations\Sets;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class NotIn extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::NOT_IN;
    }

    /**
     * @throws \Exception
     */
    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        // Normalize the values
        $values = Collection::wrap($filterData->value)->map(fn ($value): mixed => $this->getNormalizedValue($value));
        $nonNullValues = $values->reject(fn ($value): bool => $value === null)->values()->all();
        $hasNull = $values->contains(fn ($value): bool => $value === null);

        $column = $visualizable->getFilterWith();
        $columnBindings = $visualizable->getFilterWithBindings();
        $clauses = [];
        $bindings = [];

        // SQL's NOT IN is never true when the list holds NULL, so a null becomes its own IS NOT NULL clause
        if ($nonNullValues !== [] || ! $hasNull) {
            // You MUST have one parameter per item in the array
            $placeholders = implode(',', array_fill(0, count($nonNullValues), '?'));
            $clauses[] = "$column NOT IN ($placeholders)";
            $bindings = [...$bindings, ...$columnBindings, ...$nonNullValues];
        }

        if ($hasNull) {
            $clauses[] = "$column IS NOT NULL";
            $bindings = [...$bindings, ...$columnBindings];
        }

        // One grouped expression, so the null branch stays inside the filter set's AND/OR
        $expression = count($clauses) > 1 ? '('.implode(' AND ', $clauses).')' : $clauses[0];

        $method = $this->getQueryMethod($visualizable, $filterOperator);
        $query->$method($expression, $bindings);

        return $query;
    }
}
