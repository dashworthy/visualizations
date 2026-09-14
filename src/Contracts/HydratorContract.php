<?php

namespace Dashworthy\Visualizations\Contracts;

use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Collection;

/**
 * Fills one hydrated column, resolving a whole page of rows in one pass.
 *
 * A hydrator is handed the page's keys, never its rows, so there is nowhere to put a per-row
 * query. Scoping is the implementer's: this package holds no tenant or authorization context,
 * and a hydration source often has one the grid's own gate does not cover.
 */
interface HydratorContract
{
    /** The field keying each row, as the grid author declared it — 'ID', not 'column_ID'. */
    public function keyedBy(): string;

    /** The type of value produced, so the front-end knows how to render it. */
    public function columnType(): ColumnType|string;

    /**
     * Resolve every distinct, non-null key on the page. Called once per page.
     *
     * Keys are int|string, the only types PHP can index an array by. A key absent from the map
     * becomes null on its row; an empty map declines the work without querying at all (useful
     * when the viewer lacks permission on the source). Exceptions propagate.
     *
     * @param  Collection<int, array-key>  $keys
     * @return array<array-key, mixed> key => value
     */
    public function resolve(Collection $keys): array;
}
