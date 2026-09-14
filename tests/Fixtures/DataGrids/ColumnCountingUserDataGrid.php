<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Illuminate\Support\Collection;

/** Counts how often the grid asks its author for columns. */
class ColumnCountingUserDataGrid extends HydratingUserDataGrid
{
    public int $getColumnsCalls = 0;

    public function getColumns(): Collection
    {
        $this->getColumnsCalls++;

        return parent::getColumns();
    }
}
