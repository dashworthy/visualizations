<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Support\Collection;

class FilterSetData
{
    /** @param Collection<int, FilterData> $filters */
    public function __construct(
        public Collection $filters = new Collection,
        public FilterSetOperator $filterOperator = FilterSetOperator::AND
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'filters' => $this->filters->toArray(),
            'filter_set_operator' => $this->filterOperator->value,
        ];
    }
}
