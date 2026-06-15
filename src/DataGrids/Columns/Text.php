<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

/**
 * Class Text
 *
 * Represents a column of type "Text" in the DataGrid.
 * Provides methods to configure the display format for text values.
 */
class Text extends Column
{
    /**
     * The type of the column.
     */
    protected ColumnType|string $columnType = ColumnType::Text;

    /**
     * Sets the display format for text values.
     *
     * @param  string  $format  The format to display the text (e.g., uppercase, lowercase).
     * @return $this
     */
    public function displayFormat(string $format): static
    {
        $this->meta('format', $format);

        return $this;
    }
}
