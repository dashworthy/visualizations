<?php

namespace Dashworthy\Visualizations\Charts\Labels;

use Illuminate\Support\Traits\Macroable;
use Dashworthy\Visualizations\Abstracts\Visualizable;

/**
 * A Label represents a grouping or axis field within a chart (e.g. dates, categories).
 * Labels are distinct from Datasets, which represent measured data series.
 */
class Label extends Visualizable
{
    use Macroable;

    public function getFieldPrefix(): string
    {
        return 'label_';
    }

    /**
     * Transform the label to a standardized specification for the front-end schema.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'field' => $this->getField(),
            'header' => $this->getHeader(),
            'meta' => $this->meta,
        ];
    }
}
