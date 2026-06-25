<?php

namespace Dashworthy\Visualizations\Query;

final class VisualizationCacheResult
{
    public function __construct(
        public readonly mixed $results,
        public readonly bool $fromCache,
    ) {}
}
