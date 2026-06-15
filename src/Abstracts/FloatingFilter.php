<?php

namespace Dashworthy\Visualizations\Abstracts;

use Dashworthy\Visualizations\Enums\FloatingFilterType;

abstract class FloatingFilter extends Visualizable
{
    protected FloatingFilterType|string $floatingFilterType;

    public function getFieldPrefix(): string
    {
        return 'floating_filter_';
    }

    /**
     * @return array{field: string, header: string, type: string, meta: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->getField(),
            'header' => $this->getHeader(),
            'type' => is_string($this->floatingFilterType)
                ? $this->floatingFilterType
                : $this->floatingFilterType->value,
            'meta' => $this->meta,
        ];
    }
}
