<?php

namespace Dashworthy\Visualizations\Charts\Labels;

/**
 * A NullLabel is used when a chart has no label axis (e.g. pie charts).
 * It serializes in the schema to communicate the absence of labels,
 * but does not add any select expression to the query.
 */
class NullLabel extends Label
{
    /**
     * Create a NullLabel instance. No SQL expression or field is needed.
     */
    public static function create(): static
    {
        return new static('1', '_null');
    }

    public function toArray(): array
    {
        return [];
    }
}
