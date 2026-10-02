<?php

namespace Dashworthy\Visualizations\DataGrids\Abstracts;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnPin;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use stdClass;

abstract class Column extends Visualizable
{
    use Macroable;

    public function getFieldPrefix(): string
    {
        return 'column_';
    }

    /**
     * Never sortable or filterable once hydrated: the value does not exist when the page is chosen, so neither could
     * be honoured.
     *
     * @param  HydratorContract|class-string<HydratorContract>  $hydrator
     */
    protected function useHydrator(HydratorContract|string $hydrator): void
    {
        $this->hydrator = $hydrator;
        $this->isSortable = false;
        $this->isFilterable = false;
    }

    /** A hydrated column has no SQL to select, and none for a sort or filter to resolve against. */
    public function hasExpression(): bool
    {
        return $this->hydrator === null;
    }

    /**
     * The hydrator, resolving a class-string through the container on first use and keeping it.
     *
     * Lazily, because getColumns() runs on every request including those that never hydrate.
     *
     * @throws LogicException when the column was declared with SQL
     */
    public function getHydrator(): HydratorContract
    {
        if ($this->hydrator === null) {
            throw new LogicException(sprintf(
                "%s column '%s' was declared with SQL, not a hydrator.",
                class_basename(static::class),
                $this->getField(),
            ));
        }

        if ($this->hydrator instanceof HydratorContract) {
            return $this->hydrator;
        }

        /** @var HydratorContract $resolved */
        $resolved = app($this->hydrator);

        return $this->hydrator = $resolved;
    }

    /**
     * Fills this column on every row of a fetched page, with one resolve() for the whole page.
     *
     * @param  Collection<int, stdClass>  $rows  written in place
     * @param  string  $keyField  the payload field ('column_ID') holding each row's key
     *
     * @throws Exception
     */
    public function hydrate(Collection $rows, string $keyField): void
    {
        $field = $this->getField();

        if ($field === $keyField) {
            throw new Exception("Hydrated column '{$field}' collides with the field its hydrator keys on.");
        }

        // Strictly, because the write-back indexes by array-key identity: '01' and 1 compare equal.
        $keys = $rows
            ->map(fn (stdClass $row): mixed => $row->{$keyField} ?? null)
            ->reject(fn (mixed $key): bool => $key === null)
            ->map(fn (mixed $key): int|string => is_int($key) || is_string($key) ? $key : throw new Exception(
                "Field '{$keyField}' holds a ".get_debug_type($key).'; a hydrator can only be keyed by an int or a string.'
            ))
            ->unique(strict: true)
            ->values();

        // An empty page, or one with only null keys, should not cost a query.
        $resolved = $keys->isEmpty() ? [] : $this->getHydrator()->resolve($keys);

        foreach ($rows as $row) {
            $key = $row->{$keyField} ?? null;

            $row->{$field} = $key === null ? null : ($resolved[$key] ?? null);
        }
    }

    /**
     * Whether we want to hide the column in the grid.  This is useful for columns that you may want exposed to the
     * user, but not visible by default.
     */
    protected bool $isHidden = false;

    /**
     * Whether the column should be excluded from export.
     */
    protected bool $isExcludedFromExport = false;

    /**
     * Whether the column is sortable
     */
    protected bool $isSortable = true;

    /**
     * Whether the column is filterable
     */
    protected bool $isFilterable = true;

    /**
     * Identifies if and where the column should be pinned.
     */
    protected ColumnPin $columnPin = ColumnPin::None;

    /**
     * The type of column.  This is useful for the front end so that they understand how to present the data and what
     * filters are available.
     */
    protected ColumnType|string $columnType = ColumnType::Text;

    /**
     * Whether the column is a row key.
     */
    protected bool $isRowKey = false;

    /**
     * @var array<string, mixed>
     */
    protected array $meta = [];

    /**
     * The hydrator filling this column after its page is fetched, when it was declared with one in place of SQL.
     *
     * @var HydratorContract|class-string<HydratorContract>|null
     */
    protected HydratorContract|string|null $hydrator = null;

    /**
     * Identifies to the front end that we want to use the value of this column as a key for row selection
     *
     * @return $this
     */
    public function asRowKey(): self
    {
        $this->isRowKey = true;

        return $this;
    }

    /**
     * Conveys that we do not want to allow the user to sort
     *
     * @return $this
     */
    public function withoutSorting(): static
    {
        $this->isSortable = false;

        return $this;
    }

    /**
     * Conveys that we do not want to allow the user to filter
     *
     * @return $this
     */
    public function withoutFiltering(): static
    {
        $this->isFilterable = false;

        return $this;
    }

    /**
     * Conveys that we want to allow the column to be hidden by default
     *
     * @return $this
     */
    public function hidden(): static
    {
        $this->isHidden = true;

        return $this;
    }

    /**
     * Mark the column as excluded from export
     *
     * @return $this
     */
    public function withoutExport(): static
    {
        $this->isExcludedFromExport = true;

        return $this;
    }

    /**
     * Check if the column is excluded from export
     */
    public function isExcludedFromExport(): bool
    {
        return $this->isExcludedFromExport;
    }

    /**
     * Pin the column to the left.
     */
    public function pinLeft(): static
    {
        $this->columnPin = ColumnPin::Left;

        return $this;
    }

    /**
     * Pin the column to the right.
     */
    public function pinRight(): static
    {
        $this->columnPin = ColumnPin::Right;

        return $this;
    }

    /**
     * Transform the column to a standardized specification
     *
     * @return array{
     *     field: string,
     *     header: string,
     *     type: string,
     *     pin: string,
     *     is_row_key: bool,
     *     is_sortable: bool,
     *     is_filterable: bool,
     *     is_hidden: bool,
     *     meta:array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'field' => $this->getField(),
            'header' => $this->getHeader(),
            'type' => is_string($this->columnType) ? $this->columnType : $this->columnType->value,
            'pin' => $this->columnPin->value,
            'is_row_key' => $this->isRowKey,
            'is_sortable' => $this->isSortable,
            'is_filterable' => $this->isFilterable,
            'is_hidden' => $this->isHidden,
            'meta' => $this->meta,
        ];
    }
}
