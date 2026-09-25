<?php

namespace Dashworthy\Visualizations\Query;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class GenerateVisualizationQuery
{
    /** @var Collection<int, Visualizable> */
    private Collection $visualizables;

    private FilterOperation $filterOperation;

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  Collection<int, Visualizable>  $visualizables
     */
    public function handle(Builder $query, Collection $visualizables, VisualizationData $visualizationData): Builder
    {
        $this->visualizables = $visualizables;
        $this->filterOperation = app(FilterOperation::class);

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
     */
    private function applyFilters(Builder $query, Collection $filters, FilterSetOperator $filterSetOperator): void
    {
        foreach ($filters as $filter) {
            $visualizable = $this->getMatchingVisualizable($filter->field);
            if (empty($visualizable)) {
                continue;
            }

            $this->filterOperation->handle($query, $visualizable, $filter, $filterSetOperator);
        }
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
