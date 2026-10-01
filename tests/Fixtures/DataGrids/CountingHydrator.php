<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Collection;

/**
 * A hydrator that counts how many times it has been constructed, and takes a dependency.
 *
 * Both exist to make resolution observable. The count proves when construction happens — that
 * naming a hydrator by class-string defers it until the hydrator is actually needed, and that it
 * then happens once rather than on every access. The dependency proves the construction goes
 * through the container, since nothing else would supply it.
 */
class CountingHydrator implements HydratorContract
{
    /** Incremented on every construction; reset by the tests that assert against it. */
    public static int $constructed = 0;

    public function __construct(public readonly HydratorProbe $probe)
    {
        self::$constructed++;
    }

    public function keyedBy(): string
    {
        return 'ID';
    }

    public function columnType(): ColumnType|string
    {
        return ColumnType::Text;
    }

    public function resolve(Collection $keys): array
    {
        return [];
    }
}
