<?php

namespace Dashworthy\Visualizations\Abstracts;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\Traits\HandlesMetaData;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

abstract class Visualizable
{
    use HandlesMetaData;

    protected string $selectWith;

    protected QueryBuilder $selectWithBindings;

    protected ?string $filterWith = null;

    protected ?QueryBuilder $filterWithBindings = null;

    protected string $field;

    protected string $header;

    /**
     * @param  string|HydratorContract  $expression  SQL to select or, on a DataGrid column only, a hydrator (instance or
     *                                               class-string) that fills the value after the page is fetched
     * @param  list<mixed>  $bindings
     */
    final public function __construct(string|HydratorContract $expression, string $field, array $bindings = [])
    {
        $this->field = $field;
        $this->header = (string) __($field);
        $this->selectWithBindings = DB::query();

        if ($expression instanceof HydratorContract || self::namesHydrator($expression)) {
            if ($bindings !== []) {
                throw new InvalidArgumentException(sprintf(
                    "%s '%s' was given bindings with a hydrator; there is no SQL for them to bind to.",
                    class_basename(static::class),
                    $field,
                ));
            }

            $this->selectWith('');
            $this->useHydrator($expression);

            return;
        }

        $this->selectWith($expression);

        foreach ($bindings as $binding) {
            $this->addSelectWithBinding($binding);
        }
    }

    /**
     * @param  string|HydratorContract  $expression  see the constructor
     * @param  list<mixed>  $bindings
     */
    final public static function make(string|HydratorContract $expression, string $field, array $bindings = []): static
    {
        return new static($expression, $field, $bindings);
    }

    /**
     * Whether a string names a hydrator class rather than holding SQL. SQL that qualifies a column, calls a function,
     * or spans words never reaches the autoloader.
     *
     * @phpstan-assert-if-true class-string<HydratorContract> $expression
     */
    private static function namesHydrator(string $expression): bool
    {
        if (strpbrk($expression, '. (') !== false) {
            return false;
        }

        return is_a($expression, HydratorContract::class, true);
    }

    /**
     * Takes the hydrator this was declared with in place of SQL. Only a DataGrid column can be filled after its page
     * is fetched, so everything else refuses.
     *
     * @param  HydratorContract|class-string<HydratorContract>  $hydrator
     *
     * @throws LogicException
     */
    protected function useHydrator(HydratorContract|string $hydrator): void
    {
        throw new LogicException(static::class.' cannot be hydrated; only DataGrid columns can.');
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

    /**
     * Whether this carries SQL for the statement to select, filter, or sort by.
     *
     * False only for a value that arrives after the page is fetched, which the statement can
     * neither select nor resolve a client's sort or filter against.
     */
    public function hasExpression(): bool
    {
        return true;
    }

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
