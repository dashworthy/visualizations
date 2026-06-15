<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

/**
 * Class Number
 *
 * Represents a column of type "Number" in the DataGrid.
 * Provides methods to configure the display format for numeric values.
 */
class Number extends Column
{
    /**
     * The type of the column.
     */
    protected ColumnType|string $columnType = ColumnType::Number;

    /**
     * Sets the display format for numeric values.
     *
     * @param  string  $format  The format to display the number (e.g., '0.00', '#,##0').
     * @return $this
     */
    public function displayFormat(string $format): static
    {
        $this->meta('format', $format);

        return $this;
    }
}
