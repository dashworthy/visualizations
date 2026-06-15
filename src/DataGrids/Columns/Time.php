<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

/**
 * Class Time
 *
 * Represents a column of type "Time" in the DataGrid.
 * Provides methods to configure the display format for time values.
 */
class Time extends Column
{
    /**
     * The type of the column.
     */
    protected ColumnType|string $columnType = ColumnType::Time;

    /**
     * Sets the display format for time values.
     *
     * @param  string  $displayFormat  The format to display the time (e.g., 'H:i:s', 'h:i A').
     * @return $this
     */
    public function displayFormat(string $displayFormat): static
    {
        $this->meta('format', $displayFormat);

        return $this;
    }
}
