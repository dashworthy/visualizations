<?php

namespace Dashworthy\Visualizations\Charts\Datasets;

use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Enums\DatasetType;

class Bar extends Dataset
{
    protected DatasetType $datasetType = DatasetType::Bar;

    /**
     * Stack this bar series with other stacked series.
     */
    public function stacked(): static
    {
        return $this->meta('stack', 'default');
    }

    /**
     * Assign this bar series to a named stack group.
     */
    public function stackGroup(string $group): static
    {
        return $this->meta('stack', $group);
    }
}
