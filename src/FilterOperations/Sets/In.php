<?php

namespace Dashworthy\Visualizations\FilterOperations\Sets;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class In extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::IN;
    }

    /**
     * @throws \Exception
     */
    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        // Normalize the values
        $values = Collection::wrap($filterData->value)->map(fn ($value): mixed => $this->getNormalizedValue($value))->toArray();

        // You MUST have one parameter per item in the array
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $bindings = array_merge($visualizable->getFilterWithBindings(), $values);

        // Build the expression
        $expression = $visualizable->getFilterWith()." IN ($placeholders)";

        $method = $this->getQueryMethod($visualizable, $filterOperator);
        $query->$method($expression, $bindings);

        // If one of the values is null, we need to add a whereNull clause
        if (in_array(null, $values)) {
            $query->orWhereNull($visualizable->getFilterWith());
        }

        return $query;
    }
}
