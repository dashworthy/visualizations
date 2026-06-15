<?php

namespace Dashworthy\Visualizations\Charts\Datasets;

use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Enums\DatasetType;

class Scatter extends Dataset
{
    protected DatasetType $datasetType = DatasetType::Scatter;
}
