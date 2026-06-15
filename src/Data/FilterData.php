<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Enums\FilterOperator;

class FilterData
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
