<?php

namespace Dashworthy\Visualizations\Metrics;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Illuminate\Support\Traits\Macroable;

/**
 * A Value represents the primary scalar aggregate for a Metric (e.g. total revenue, active users).
 * Values are distinct from Datasets, which represent chart series data.
 */
class Value extends Visualizable
{
    use Macroable;

    public function getFieldPrefix(): string
    {
        return 'value_';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'field' => $this->getField(),
            'header' => $this->getHeader(),
            'meta' => $this->meta,
        ];
    }
}
