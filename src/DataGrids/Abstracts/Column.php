<?php

namespace Dashworthy\Visualizations\DataGrids\Abstracts;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnPin;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Traits\Macroable;

abstract class Column extends Visualizable
{
    use Macroable;

    public function getFieldPrefix(): string
    {
        return 'column_';
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
