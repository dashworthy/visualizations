<?php

namespace Dashworthy\Visualizations\Builders;

use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

class FilterBuilder
{
    use Macroable;

    /** @var Collection<int, FilterData> */
    protected Collection $filters;

    public function __construct()
    {
        $this->filters = collect();
    }

    public function addFilter(string $field, mixed $value, FilterOperator $filterOperator): self
    {
        $this->filters->push(new FilterData($field, $value, $filterOperator));

        return $this;
    }

    public function startsWith(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::STRING_STARTS_WITH);
    }

    public function contains(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::STRING_CONTAINS);
    }

    public function doesNotContain(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::STRING_DOES_NOT_CONTAIN);
    }

    public function endsWith(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::STRING_ENDS_WITH);
    }

    public function equals(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::EQUALS);
    }

    public function notEquals(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::NOT_EQUALS);
    }

    public function in(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::IN);
    }

    public function notIn(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::NOT_IN);
    }

    public function lessThan(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::LESS_THAN);
    }

    public function lessThanOrEqualTo(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::LESS_THAN_OR_EQUAL_TO);
    }

    public function greaterThan(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::GREATER_THAN);
    }

    public function greaterThanOrEqualTo(string $field, mixed $value): self
    {
        return $this->addFilter($field, $value, FilterOperator::GREATER_THAN_OR_EQUAL_TO);
    }

    /** @return Collection<int, FilterData> */
    public function getFilters(): Collection
    {
        return $this->filters;
    }
}
