<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

/**
 * Class DateTime
 *
 * Represents a column of type "DateTime" in the DataGrid.
 * Provides methods to configure the display format for date-time values.
 */
class DateTime extends Column
{
    /**
     * The type of the column.
     */
    protected ColumnType|string $columnType = ColumnType::DateTime;

    /**
     * Sets the display format for date-time values.
     *
     * @param  string  $format  The format to display the date-time (e.g., 'Y-m-d H:i:s', 'd/m/Y H:i').
     * @return $this
     */
    public function displayFormat(string $format): static
    {
        $this->meta('format', $format);

        return $this;
    }
}
