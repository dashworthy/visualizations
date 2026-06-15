<?php

namespace Dashworthy\Visualizations\Charts\Datasets;

use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Enums\DatasetType;

class Pie extends Dataset
{
    protected DatasetType $datasetType = DatasetType::Pie;
}
