<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

/**
 * Class Boolean
 *
 * Represents a column of type "Boolean" in the DataGrid.
 * Provides methods to configure the display format for truthy and falsy values.
 */
class Boolean extends Column
{
    /**
     * The type of the column.
     */
    protected ColumnType|string $columnType = ColumnType::Boolean;

    /**
     * Sets the display format for truthy and falsy values.
     *
     * @param  string  $truthyFormat  The format to display for truthy values.
     * @param  string  $falsyFormat  The format to display for falsy values.
     */
    public function displayFormat(string $truthyFormat, string $falsyFormat): self
    {
        $this->meta('format', [
            'truthy' => $truthyFormat,
            'falsy' => $falsyFormat,
        ]);

        return $this;
    }
}
