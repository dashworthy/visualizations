<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Illuminate\Support\Collection;

/**
 * A grid with one hydrated column, over a fixed map rather than a second table.
 *
 * Both branches of handleData() can be exercised without a hydration source to migrate. The
 * hydrator is held so a test can read how many times it resolved; it cannot be built in a
 * constructor, because DataGrid's is final.
 */
class HydratingUserDataGrid extends UserDataGrid
{
    protected StaticHydrator $hydrator;

    public function hydrator(): StaticHydrator
    {
        return $this->hydrator ??= new StaticHydrator([1 => 'first note', 2 => 'second note']);
    }

    public function getColumns(): Collection
    {
        return parent::getColumns()->push(
            HydratedColumn::for($this->hydrator(), 'Notes'),
        );
    }
}
