<?php

namespace Dashworthy\Visualizations\Charts\Datasets;

use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Enums\DatasetType;

class Line extends Dataset
{
    protected DatasetType $datasetType = DatasetType::Line;

    /**
     * Control the line tension/curvature (0 = straight, 1 = maximum curve).
     */
    public function tension(float $value): static
    {
        return $this->meta('tension', $value);
    }

    /**
     * Fill the area beneath the line.
     */
    public function filled(): static
    {
        return $this->meta('fill', true);
    }
}
