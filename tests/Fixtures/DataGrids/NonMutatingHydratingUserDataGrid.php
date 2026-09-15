<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Illuminate\Support\Collection;
use stdClass;

/**
 * Hydrates without touching the rows it was given, returning fresh ones instead.
 *
 * HydrateVisualizationRows writes onto the rows in place, so the paginated branch would look
 * correct even if it ignored what hydrate() returned. This grid removes that coincidence: only
 * the returned collection carries the value.
 */
class NonMutatingHydratingUserDataGrid extends HydratingUserDataGrid
{
    public const NOTE = 'from the returned collection';

    public function hydrate(Collection $rows): Collection
    {
        return $rows->map(function (stdClass $row): stdClass {
            $fresh = clone $row;
            $fresh->column_Notes = self::NOTE;

            return $fresh;
        });
    }
}
