<?php

namespace Dashworthy\Visualizations\Query;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;

class FilterOperation
{
    /**
     * Applies the filter's condition as a where or having clause, joined to its filter set with AND or OR.
     */
    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterSetOperator = FilterSetOperator::AND): Builder
    {
        [$expression, $bindings] = $this->compile($visualizable, $filterData);

        $method = $this->getQueryMethod($visualizable, $filterSetOperator);
        $query->$method($expression, $bindings);

        // A null in an IN or NOT IN list needs its own clause
        if ($this->isSetOperator($filterData->filterOperator) && in_array(null, $this->getNormalizedValues($filterData))) {
            $filterData->filterOperator === FilterOperator::IN
                ? $query->orWhereNull($visualizable->getFilterWith())
                : $query->orWhereNotNull($visualizable->getFilterWith());
        }

        return $query;
    }

    /**
     * The SQL condition for the filter's operator, with a `?` for each binding, and its bindings in placeholder
     * order: the visualizable's filter bindings, then the filter value.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function compile(Visualizable $visualizable, FilterData $filterData): array
    {
        $column = $visualizable->getFilterWith();
        $columnBindings = $visualizable->getFilterWithBindings();
        $value = $filterData->value;

        return match ($filterData->filterOperator) {
            FilterOperator::EQUALS => $value === null
                ? ["$column IS NULL", $columnBindings]
                : ["$column = ?", [...$columnBindings, $value]],
            FilterOperator::NOT_EQUALS => $value === null
                ? ["$column IS NOT NULL", $columnBindings]
                : ["$column != ?", [...$columnBindings, $value]],
            FilterOperator::LESS_THAN => ["$column < ?", [...$columnBindings, $value]],
            FilterOperator::LESS_THAN_OR_EQUAL_TO => ["$column <= ?", [...$columnBindings, $value]],
            FilterOperator::GREATER_THAN => ["$column > ?", [...$columnBindings, $value]],
            FilterOperator::GREATER_THAN_OR_EQUAL_TO => ["$column >= ?", [...$columnBindings, $value]],
            FilterOperator::IN => $this->compileSet($column, $columnBindings, 'IN', $filterData),
            FilterOperator::NOT_IN => $this->compileSet($column, $columnBindings, 'NOT IN', $filterData),
            FilterOperator::STRING_CONTAINS => ["$column LIKE ?", [...$columnBindings, '%'.$value.'%']],
            FilterOperator::STRING_DOES_NOT_CONTAIN => ["($column NOT LIKE ? OR $column IS NULL)", [...$columnBindings, '%'.$value.'%']],
            FilterOperator::STRING_STARTS_WITH => ["$column LIKE ?", [...$columnBindings, $value.'%']],
            FilterOperator::STRING_ENDS_WITH => ["$column LIKE ?", [...$columnBindings, '%'.$value]],
        };
    }

    public function getNormalizedValue(mixed $value): mixed
    {
        $normalizers = config('visualizations.normalizers');

        /** @var Pipeline $pipeline */
        $pipeline = app(Pipeline::class);

        return $pipeline->send($value)
            ->through($normalizers)
            ->thenReturn();
    }

    public function getQueryMethod(Visualizable $visualizable, FilterSetOperator $filterSetOperator): string
    {
        $queryMethod = $visualizable->isHavingRequired()
            ? 'havingRaw'
            : 'whereRaw';

        if ($filterSetOperator === FilterSetOperator::OR) {
            return 'or'.ucfirst($queryMethod);
        }

        return $queryMethod;
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileSet(string $column, array $columnBindings, string $operator, FilterData $filterData): array
    {
        $values = $this->getNormalizedValues($filterData);

        // You MUST have one parameter per item in the array
        $placeholders = implode(',', array_fill(0, count($values), '?'));

        return ["$column $operator ($placeholders)", [...$columnBindings, ...$values]];
    }

    private function isSetOperator(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::IN || $filterOperator === FilterOperator::NOT_IN;
    }

    /**
     * @return array<int, mixed>
     */
    private function getNormalizedValues(FilterData $filterData): array
    {
        return Collection::wrap($filterData->value)->map(fn ($value): mixed => $this->getNormalizedValue($value))->toArray();
    }
}
