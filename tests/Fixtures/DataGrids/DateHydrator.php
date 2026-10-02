<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Collection;

/**
 * A hydrator producing a date for every key, for the date-typed columns' tests.
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
        return $keys->mapWithKeys(fn (int|string $key): array => [$key => '2026-10-02'])->all();
    }
}
