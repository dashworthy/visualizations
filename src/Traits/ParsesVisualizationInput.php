<?php

namespace Dashworthy\Visualizations\Traits;

use Illuminate\Support\Collection;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Enums\SortOperator;

trait ParsesVisualizationInput
{
    /** @param array<int, array{filter_set_operator: string, filters: array<int, array{field: string, value: mixed, filter_operator: string}>}> $parsableFilterSets
     * @return Collection<int, FilterSetData> */
    public function parseFilterSets(array $parsableFilterSets): Collection
    {
        $filterSets = collect();

        foreach ($parsableFilterSets as $parsableFilterSet) {
            $builder = new FilterBuilder;
            foreach ($parsableFilterSet['filters'] as $filter) {
                $builder->addFilter(
                    $filter['field'],
                    $filter['value'],
                    FilterOperator::from($filter['filter_operator'])
                );
            }
            $filterSets->push(new FilterSetData(
                $builder->getFilters(),
                FilterSetOperator::from($parsableFilterSet['filter_set_operator'])
            ));
        }

        return $filterSets;
    }

    /** @param array<int, array{field: string, sort_operator: string}> $parsableSorts
     * @return Collection<int, SortData> */
    public function parseSorts(array $parsableSorts): Collection
    {
        $sorts = collect();

        foreach ($parsableSorts as $parsableSort) {
            $sorts->push(new SortData(
                $parsableSort['field'],
                SortOperator::from($parsableSort['sort_operator'])
            ));
        }

        return $sorts;
    }
}
