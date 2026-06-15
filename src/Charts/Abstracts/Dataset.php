<?php

namespace Dashworthy\Visualizations\Charts\Abstracts;

use Illuminate\Support\Traits\Macroable;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Charts\Enums\DatasetType;

/**
 * A Dataset represents a single series of data within a chart.  Datasets are visualizables, meaning they carry
 * a SQL expression which is selected from the base query and returned as part of the chart payload.
 */
abstract class Dataset extends Visualizable
{
    use Macroable;

    public function getFieldPrefix(): string
    {
        return 'dataset_';
    }

    /**
     * The type of dataset, which tells the front-end how to render this series.
     */
    protected DatasetType $datasetType;

    /**
     * Transform the dataset to a standardized specification for the front-end schema.
     *
     * @return array{
     *     field: string,
     *     header: string,
     *     type: string,
     *     meta: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'field' => $this->getField(),
            'header' => $this->getHeader(),
            'type' => $this->datasetType->value,
            'meta' => $this->meta,
        ];
    }
}
