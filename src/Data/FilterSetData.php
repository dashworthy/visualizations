<?php

namespace Dashworthy\Visualizations\Data;

use Illuminate\Support\Collection;
use Dashworthy\Visualizations\Enums\FilterSetOperator;

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
