<?php

namespace Dashworthy\Visualizations\Events;

class VisualizationQueryExecuted
{
    public function __construct(
        public readonly string $visualizationKey,
        public readonly string $visualizationType,
        public readonly string $sql,
        public readonly float $durationMs,
        public readonly int $rowCount,
    ) {}
}
