<?php

namespace Dashworthy\Visualizations\Abstracts;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\Traits\HandlesMetaData;

abstract class Visualizable
{
    use HandlesMetaData;

    protected string $selectWith;

    protected QueryBuilder $selectWithBindings;

    protected ?string $filterWith = null;

    protected ?QueryBuilder $filterWithBindings = null;

    protected string $field;

    protected string $header;

    /** @param list<mixed> $bindings */
    final public function __construct(string $expression, string $field, array $bindings = [])
    {
        $this->field = $field;
        $this->header = (string) __($field);
        $this->selectWithBindings = DB::query();

        $this->selectWith($expression);

        foreach ($bindings as $binding) {
            $this->addSelectWithBinding($binding);
        }
    }

    /** @param list<mixed> $bindings */
    final public static function make(string $expression, string $field, array $bindings = []): static
    {
        return new static($expression, $field, $bindings);
    }

    public function addSelectWithBinding(mixed $value): self
    {
        $this->selectWithBindings->addBinding($value);

        return $this;
    }

    /** @return list<mixed> */
    public function getSelectWithBindings(): array
    {
        return $this->selectWithBindings->getBindings();
    }

    public function addFilterWithBinding(mixed $value): self
    {
        if ($this->filterWithBindings === null) {
            $this->filterWithBindings = DB::query();
        }

        $this->filterWithBindings->addBinding($value);

        return $this;
    }

    /** @return list<mixed> */
    public function getFilterWithBindings(): array
    {
        return $this->filterWithBindings?->getBindings() ?? $this->selectWithBindings->getBindings();
    }

    abstract public function getFieldPrefix(): string;

    public function getField(): string
    {
        return $this->getFieldPrefix().$this->field;
    }

    protected function selectWith(string $selectWith): static
    {
        $this->selectWith = $selectWith;

        return $this;
    }

    public function getSelectWith(): string
    {
        return $this->selectWith;
    }

    public function getFilterWith(): string
    {
        return $this->filterWith ?? $this->selectWith;
    }

    public function isHavingRequired(): bool
    {
        foreach (['count(', 'sum(', 'avg(', 'min(', 'max('] as $expression) {
            if (str_contains(strtolower($this->selectWith), $expression)) {
                return true;
            }
        }

        return false;
    }

    public function header(string $header): static
    {
        $this->header = $header;

        return $this;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    /** @return array<string, mixed> */
    abstract public function toArray(): array;
}
