<?php

namespace Dashworthy\Visualizations\Charts\Datasets;

use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Enums\DatasetType;

class Area extends Dataset
{
    protected DatasetType $datasetType = DatasetType::Area;

    /**
     * Control the line tension/curvature (0 = straight, 1 = maximum curve).
     */
    public function tension(float $value): static
    {
        return $this->meta('tension', $value);
    }
}
