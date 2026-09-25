<?php

namespace Dashworthy\Visualizations\Data;

/**
 * What a visualization's fetch step produced: the payload to send back, and
 * how many rows it holds for the `VisualizationQueryExecuted` event.
 */
class FetchedData
{
    public function __construct(
        public readonly mixed $payload,
        public readonly int $rowCount,
    ) {}
}
