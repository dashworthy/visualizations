<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Collection;

/**
 * A hydrator declaring a column type other than the Text default.
 *
 * Exists so a test can tell "the column reports the hydrator's type" apart from "the column
 * happens to hard-code Text" — two claims a Text-returning hydrator cannot distinguish.
 */
class DateHydrator implements HydratorContract
{
    public function keyedBy(): string
    {
        return 'ID';
    }

    public function columnType(): ColumnType|string
    {
        return ColumnType::Date;
    }

    public function resolve(Collection $keys): array
    {
        return [];
    }
}
