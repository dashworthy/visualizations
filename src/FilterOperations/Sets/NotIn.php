<?php

namespace Dashworthy\Visualizations\FilterOperations\Sets;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Illuminate\Support\Collection;

class NotIn extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::NOT_IN;
    }

    protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        $column = $visualizable->getFilterWith();
        [$values, $hasNull] = $this->getValuesWithoutNull($filterData);

        // You MUST have one parameter per item in the array
        $placeholders = implode(',', array_fill(0, count($values), '?'));

        if (! $hasNull) {
            return "$column NOT IN ($placeholders)";
        }

        // NOT IN never matches when its list holds a null, so the null is excluded with IS NOT NULL instead
        if ($values === []) {
            return "$column IS NOT NULL";
        }

        return "($column NOT IN ($placeholders) AND $column IS NOT NULL)";
    }

    protected function buildBindings(Visualizable $visualizable, FilterData $filterData): array
    {
        [$values, $hasNull] = $this->getValuesWithoutNull($filterData);

        if (! $hasNull) {
            return [...$visualizable->getFilterWithBindings(), ...$values];
        }

        if ($values === []) {
            return $visualizable->getFilterWithBindings();
        }

        return [...$visualizable->getFilterWithBindings(), ...$values, ...$visualizable->getFilterWithBindings()];
    }

    /**
     * The normalized values with any null removed, and whether there was one.
     *
     * @return array{0: array<int, mixed>, 1: bool}
     */
    private function getValuesWithoutNull(FilterData $filterData): array
    {
        $values = Collection::wrap($filterData->value)->map(fn ($value): mixed => $this->getNormalizedValue($value));
        $withoutNull = $values->reject(fn (mixed $value): bool => $value === null)->values()->all();

        return [$withoutNull, count($withoutNull) !== $values->count()];
    }
}
