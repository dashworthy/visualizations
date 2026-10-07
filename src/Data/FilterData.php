<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Enums\FilterOperator;
use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, mixed> */
class FilterData implements Arrayable
{
    public function __construct(
        public string $field,
        public mixed $value,
        public FilterOperator $filterOperator,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'value' => $this->value,
            'filter_operator' => $this->filterOperator->value,
        ];
    }
}
