<?php

namespace Dashworthy\Visualizations\Charts\Datasets;

use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Enums\DatasetType;

class Doughnut extends Dataset
{
    protected DatasetType $datasetType = DatasetType::Doughnut;

    /**
     * Set the cutout percentage of the doughnut (how large the hole is).
     */
    public function cutout(string $percentage): static
    {
        return $this->meta('cutout', $percentage);
    }
}
