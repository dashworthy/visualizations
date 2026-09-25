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

    protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        // You MUST have one parameter per item in the array
        $placeholders = implode(',', array_fill(0, count($this->getNormalizedValues($filterData)), '?'));

        return $visualizable->getFilterWith()." IN ($placeholders)";
    }

    protected function buildBindings(Visualizable $visualizable, FilterData $filterData): array
    {
        return [...$visualizable->getFilterWithBindings(), ...$this->getNormalizedValues($filterData)];
    }

    /**
     * @throws \Exception
     */
    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        parent::handle($query, $visualizable, $filterData, $filterOperator);

        // If one of the values is null, we need to add a whereNull clause
        if (in_array(null, $this->getNormalizedValues($filterData))) {
            $query->orWhereNull($visualizable->getFilterWith());
        }

        return $query;
    }

    /**
     * @return array<int, mixed>
     */
    private function getNormalizedValues(FilterData $filterData): array
    {
        return Collection::wrap($filterData->value)->map(fn ($value): mixed => $this->getNormalizedValue($value))->toArray();
    }
}
