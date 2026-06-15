<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

/**
 * Class Date
 *
 * Represents a column of type "Date" in the DataGrid.
 * Provides methods to configure the display format for date values.
 */
class Date extends Column
{
    /**
     * The type of the column.
     */
    protected ColumnType|string $columnType = ColumnType::Date;

    /**
     * Sets the display format for date values.
     *
     * @param  string  $dateFormat  The format to display the date (e.g., 'Y-m-d', 'd/m/Y').
     * @return $this
     */
    public function displayFormat(string $dateFormat): static
    {
        $this->meta('format', $dateFormat);

        return $this;
    }
}
