<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Enums\FilterOperator;

class FilterData
{
    public function __construct(
        public string $field,
        public mixed $value,
        public FilterOperator|string $filterOperator,
    ) {}

    /**
     * The operator's wire key: the enum's value for a built-in operator, the macro name for a custom one.
     */
    public function getOperatorKey(): string
    {
        return $this->filterOperator instanceof FilterOperator
            ? $this->filterOperator->value
            : $this->filterOperator;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'value' => $this->value,
            'filter_operator' => $this->getOperatorKey(),
        ];
    }
}
