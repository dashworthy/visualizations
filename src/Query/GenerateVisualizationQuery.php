<?php

namespace Dashworthy\Visualizations\Query;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class GenerateVisualizationQuery
{
    /** @var Collection<int, Visualizable> */
    private Collection $visualizables;

    /** @var Collection<int, FilterOperationContract>|null */
    private ?Collection $filterOperations = null;

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  Collection<int, Visualizable>  $visualizables
     *
     * @throws Exception
     */
    public function handle(Builder $query, Collection $visualizables, VisualizationData $visualizationData): Builder
    {
        $this->visualizables = $visualizables;

        $this->applyFilterSets($query, $visualizationData->filterSets);
        $this->applySorts($query, $visualizationData->sorts);

        foreach ($visualizables as $visualizable) {
            if (! $visualizable instanceof FloatingFilter) {
                $query->selectRaw("{$visualizable->getSelectWith()} as `{$visualizable->getField()}`", $visualizable->getSelectWithBindings());
            }
        }

        return $query;
    }

    private function getMatchingVisualizable(string $field): ?Visualizable
    {
        return $this->visualizables->where(function (Visualizable $visualizable) use ($field) {
            return $visualizable->getField() === $field;
        })->first();
    }

    /**
     * Groups each set's row filters into a nested where and its aggregate filters into a nested having. A nested
     * where keeps only its where clauses, so an aggregate filter placed in one would be dropped.
     *
     * @param  Collection<int, FilterSetData>  $filterSets
     *
     * @throws Exception
     */
    private function applyFilterSets(Builder $query, Collection $filterSets): void
    {
        foreach ($filterSets as $filterSet) {
            [$aggregateFilters, $rowFilters] = $filterSet->filters->partition(
                fn (FilterData $filter): bool => $this->getMatchingVisualizable($filter->field)?->isHavingRequired() ?? false
            );

            // Row filters run before grouping and aggregate filters after, so an OR across the two has no single clause to go in
            if ($filterSet->filterOperator === FilterSetOperator::OR && $aggregateFilters->isNotEmpty() && $rowFilters->isNotEmpty()) {
                throw new Exception('A filter set cannot OR an aggregate filter with a row filter');
            }

            if ($rowFilters->isNotEmpty()) {
                $query->where(function (Builder $query) use ($rowFilters, $filterSet): void {
                    $this->applyFilters($query, $rowFilters, $filterSet->filterOperator);
                });
            }

            // Only when there is one, since havingNested() fails on a group left empty
            if ($aggregateFilters->isNotEmpty()) {
                $query->havingNested(function (Builder $query) use ($aggregateFilters, $filterSet): void {
                    $this->applyFilters($query, $aggregateFilters, $filterSet->filterOperator);
                });
            }
        }
    }

    /**
     * @param  Collection<int, FilterData>  $filters
     *
     * @throws Exception
     */
    private function applyFilters(Builder $query, Collection $filters, FilterSetOperator $filterSetOperator): void
    {
        foreach ($filters as $filter) {
            $visualizable = $this->getMatchingVisualizable($filter->field);
            if (empty($visualizable)) {
                continue;
            }

            $filterClass = $this->getMatchingFilterClass($visualizable, $filter);
            if (! $filterClass instanceof FilterOperationContract) {
                throw new Exception("No filter operation found for {$visualizable->getField()} with filter operator {$filter->filterOperator->value}");
            }
            $filterClass->handle($query, $visualizable, $filter, $filterSetOperator);
        }
    }

    private function getMatchingFilterClass(Visualizable $visualizable, FilterData $filter): ?FilterOperationContract
    {
        // Resolved once per query rather than once per filter
        $this->filterOperations ??= collect(config('visualizations.filters'))
            ->map(fn (string $filterClass): FilterOperationContract => app($filterClass));

        return $this->filterOperations->first(
            fn (FilterOperationContract $filterOperation): bool => $filterOperation->canHandle($filter->filterOperator)
        );
    }

    /**
     * @param  Collection<int, SortData>  $sorts
     */
    private function applySorts(Builder $query, Collection $sorts): void
    {
        foreach ($sorts as $sort) {
            $visualizable = $this->getMatchingVisualizable($sort->field);

            if (! $visualizable instanceof Visualizable) {
                continue;
            }

            $query->orderBy($visualizable->getField(), $sort->sortOperator->value);
        }
    }
}
