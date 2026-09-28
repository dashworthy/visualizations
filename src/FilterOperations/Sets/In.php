<?php

namespace Dashworthy\Visualizations\FilterOperations\Sets;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Illuminate\Support\Collection;

class In extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::IN;
    }

    protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        $column = $visualizable->getFilterWith();
        [$values, $hasNull] = $this->getValuesWithoutNull($filterData);

        // You MUST have one parameter per item in the array
        $placeholders = implode(',', array_fill(0, count($values), '?'));

        if (! $hasNull) {
            return "$column IN ($placeholders)";
        }

        // IN never matches a null, so the null is matched with IS NULL instead
        if ($values === []) {
            return "$column IS NULL";
        }

        return "($column IN ($placeholders) OR $column IS NULL)";
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
