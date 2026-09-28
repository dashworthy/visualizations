<?php

namespace Dashworthy\Visualizations\Traits;

use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;

trait ParsesVisualizationInput
{
    /** @param array<int, array{filter_set_operator: string, filters: array<int, array{field: string, value: mixed, filter_operator: string}>}> $parsableFilterSets
     * @return Collection<int, FilterSetData> */
    public function parseFilterSets(array $parsableFilterSets): Collection
    {
        $filterSets = collect();

        /** @var Pipeline $pipeline */
        $pipeline = app(Pipeline::class);

        /** @var array<int, mixed> $normalizers */
        $normalizers = config('visualizations.normalizers');

        foreach ($parsableFilterSets as $parsableFilterSet) {
            $builder = new FilterBuilder;
            foreach ($parsableFilterSet['filters'] as $filter) {
                $filterOperator = FilterOperator::from($filter['filter_operator']);

                $builder->addFilter(
                    $filter['field'],
                    $filterOperator->normalizesValue()
                        ? $this->normalizeFilterValue($filter['value'], $pipeline, $normalizers)
                        : $filter['value'],
                    $filterOperator
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

    /**
     * Runs a request value through the normalizers, a list value item by item.
     *
     * @param  array<int, mixed>  $normalizers
     */
    private function normalizeFilterValue(mixed $value, Pipeline $pipeline, array $normalizers): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeFilterValue($item, $pipeline, $normalizers), $value);
        }

        return $pipeline->send($value)->through($normalizers)->thenReturn();
    }
}
