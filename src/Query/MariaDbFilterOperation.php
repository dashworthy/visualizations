<?php

namespace Dashworthy\Visualizations\Query;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Compiles a filter into a MariaDB where or having clause. Each built-in operator has its own method.
 *
 * An application replaces an operator by overriding its method, or adds one by overriding operators() and
 * compile(), then binds its subclass to FilterOperationContract:
 *
 *     class AppFilterOperation extends MariaDbFilterOperation
 *     {
 *         public function operators(): array
 *         {
 *             return [...parent::operators(), 'regexp'];
 *         }
 *
 *         protected function compile(Visualizable $visualizable, FilterData $filterData): array
 *         {
 *             return match ($filterData->getOperatorKey()) {
 *                 'regexp' => [$visualizable->getFilterWith().' REGEXP ?', [...$visualizable->getFilterWithBindings(), $filterData->value]],
 *                 default => parent::compile($visualizable, $filterData),
 *             };
 *         }
 *     }
 */
class MariaDbFilterOperation implements FilterOperationContract
{
    /**
     * @return list<string>
     */
    public function operators(): array
    {
        return array_map(fn (FilterOperator $filterOperator): string => $filterOperator->value, FilterOperator::cases());
    }

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterSetOperator = FilterSetOperator::AND): Builder
    {
        [$expression, $bindings] = $this->compile($visualizable, $filterData);

        $method = $this->getQueryMethod($visualizable, $filterSetOperator);
        $query->$method($expression, $bindings);

        // A null in an IN list needs its own clause
        if ($filterData->filterOperator === FilterOperator::IN && in_array(null, $this->getNormalizedValues($filterData->value))) {
            $query->orWhereNull($visualizable->getFilterWith());
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

        if (! $filterData->filterOperator instanceof FilterOperator) {
            throw new InvalidArgumentException("No filter operator named [{$filterData->getOperatorKey()}].");
        }

        return match ($filterData->filterOperator) {
            FilterOperator::EQUALS => $this->equals($column, $columnBindings, $value),
            FilterOperator::NOT_EQUALS => $this->notEquals($column, $columnBindings, $value),
            FilterOperator::LESS_THAN => $this->lessThan($column, $columnBindings, $value),
            FilterOperator::LESS_THAN_OR_EQUAL_TO => $this->lessThanOrEqualTo($column, $columnBindings, $value),
            FilterOperator::GREATER_THAN => $this->greaterThan($column, $columnBindings, $value),
            FilterOperator::GREATER_THAN_OR_EQUAL_TO => $this->greaterThanOrEqualTo($column, $columnBindings, $value),
            FilterOperator::IN => $this->in($column, $columnBindings, $value),
            FilterOperator::NOT_IN => $this->notIn($column, $columnBindings, $value),
            FilterOperator::STRING_CONTAINS => $this->contains($column, $columnBindings, $value),
            FilterOperator::STRING_DOES_NOT_CONTAIN => $this->doesNotContain($column, $columnBindings, $value),
            FilterOperator::STRING_STARTS_WITH => $this->startsWith($column, $columnBindings, $value),
            FilterOperator::STRING_ENDS_WITH => $this->endsWith($column, $columnBindings, $value),
        };
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function equals(string $column, array $columnBindings, mixed $value): array
    {
        if ($value === null) {
            return ["$column IS NULL", $columnBindings];
        }

        return ["$column = ?", [...$columnBindings, $value]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function notEquals(string $column, array $columnBindings, mixed $value): array
    {
        if ($value === null) {
            return ["$column IS NOT NULL", $columnBindings];
        }

        return ["$column != ?", [...$columnBindings, $value]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function lessThan(string $column, array $columnBindings, mixed $value): array
    {
        return ["$column < ?", [...$columnBindings, $value]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function lessThanOrEqualTo(string $column, array $columnBindings, mixed $value): array
    {
        return ["$column <= ?", [...$columnBindings, $value]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function greaterThan(string $column, array $columnBindings, mixed $value): array
    {
        return ["$column > ?", [...$columnBindings, $value]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function greaterThanOrEqualTo(string $column, array $columnBindings, mixed $value): array
    {
        return ["$column >= ?", [...$columnBindings, $value]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function in(string $column, array $columnBindings, mixed $value): array
    {
        return $this->compileSet($column, $columnBindings, 'IN', $this->getNormalizedValues($value));
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function notIn(string $column, array $columnBindings, mixed $value): array
    {
        [$values, $hasNull] = $this->withoutNull($value);

        if (! $hasNull) {
            return $this->compileSet($column, $columnBindings, 'NOT IN', $values);
        }

        // NOT IN never matches when its list holds a null, so the null is excluded with IS NOT NULL instead
        if ($values === []) {
            return ["$column IS NOT NULL", $columnBindings];
        }

        [$expression, $bindings] = $this->compileSet($column, $columnBindings, 'NOT IN', $values);

        return ["($expression AND $column IS NOT NULL)", [...$bindings, ...$columnBindings]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function contains(string $column, array $columnBindings, mixed $value): array
    {
        return ["$column LIKE ?", [...$columnBindings, '%'.$value.'%']];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function doesNotContain(string $column, array $columnBindings, mixed $value): array
    {
        // The column is referenced twice, so its bindings are too
        return ["($column NOT LIKE ? OR $column IS NULL)", [...$columnBindings, '%'.$value.'%', ...$columnBindings]];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function startsWith(string $column, array $columnBindings, mixed $value): array
    {
        return ["$column LIKE ?", [...$columnBindings, $value.'%']];
    }

    /**
     * @param  array<int, mixed>  $columnBindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function endsWith(string $column, array $columnBindings, mixed $value): array
    {
        return ["$column LIKE ?", [...$columnBindings, '%'.$value]];
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
     * @param  array<int, mixed>  $values
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileSet(string $column, array $columnBindings, string $operator, array $values): array
    {
        // You MUST have one parameter per item in the array
        $placeholders = implode(',', array_fill(0, count($values), '?'));

        return ["$column $operator ($placeholders)", [...$columnBindings, ...$values]];
    }

    /**
     * The normalized values with any null removed, and whether there was one.
     *
     * @return array{0: array<int, mixed>, 1: bool}
     */
    private function withoutNull(mixed $value): array
    {
        $values = $this->getNormalizedValues($value);
        $withoutNull = array_values(array_filter($values, fn (mixed $value): bool => $value !== null));

        return [$withoutNull, count($withoutNull) !== count($values)];
    }

    /**
     * @return array<int, mixed>
     */
    private function getNormalizedValues(mixed $value): array
    {
        return Collection::wrap($value)->map(fn ($value): mixed => $this->getNormalizedValue($value))->toArray();
    }
}
