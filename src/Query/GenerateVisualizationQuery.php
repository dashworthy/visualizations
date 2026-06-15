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
     * @param  Collection<int, FilterSetData>  $filterSets
     *
     * @throws Exception
     */
    private function applyFilterSets(Builder $query, Collection $filterSets): void
    {
        foreach ($filterSets as $filterSet) {
            $query->where(function (Builder $query) use ($filterSet): void {
                $this->applyFilters($query, $filterSet->filters, $filterSet->filterOperator);
            });
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
        $availableFilters = config('visualizations.filters');

        foreach ($availableFilters as $filterClass) {
            /** @var FilterOperationContract $filterInstance */
            $filterInstance = app($filterClass);
            if ($filterInstance->canHandle($filter->filterOperator)) {
                return $filterInstance;
            }
        }

        return null;
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
