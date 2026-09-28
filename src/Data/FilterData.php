<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Enums\FilterOperator;
use Illuminate\Pipeline\Pipeline;

class FilterData
{
    /**
     * Runs the value through the configured normalizers, unless the operator matches it as typed.
     */
    public function __construct(
        public string $field,
        public mixed $value,
        public FilterOperator $filterOperator,
    ) {
        if ($filterOperator->normalizesValue()) {
            /** @var Pipeline $pipeline */
            $pipeline = app(Pipeline::class);

            $this->value = $this->normalize($value, $pipeline->through(config('visualizations.normalizers')));
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'value' => $this->value,
            'filter_operator' => $this->filterOperator->value,
        ];
    }

    /**
     * Normalizes a list value item by item.
     */
    private function normalize(mixed $value, Pipeline $pipeline): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item, $pipeline), $value);
        }

        return $pipeline->send($value)->thenReturn();
    }
}
