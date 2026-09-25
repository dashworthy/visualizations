<?php

namespace Dashworthy\Visualizations\Abstracts;

use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pipeline\Pipeline;

abstract class FilterOperation implements FilterOperationContract
{
    /**
     * The SQL condition for this operation, with a `?` for each binding.
     */
    abstract protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string;

    /**
     * The bindings for buildExpression(), in placeholder order: the visualizable's filter bindings for each time
     * the expression references it, then the filter value. The default fits an expression that references the
     * visualizable once and binds the value once.
     *
     * @return array<int, mixed>
     */
    protected function buildBindings(Visualizable $visualizable, FilterData $filterData): array
    {
        return [...$visualizable->getFilterWithBindings(), $filterData->value];
    }

    /**
     * Applies the expression as a where or having clause, joined to its filter set with AND or OR.
     */
    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterOperator = FilterSetOperator::AND): Builder
    {
        $method = $this->getQueryMethod($visualizable, $filterOperator);
        $query->$method(
            $this->buildExpression($visualizable, $filterData),
            $this->buildBindings($visualizable, $filterData)
        );

        return $query;
    }

    public function getNormalizedValue(mixed $value): mixed
    {
        $normalizers = config('visualizations.normalizers');

        /** @var Pipeline $pipeline */
        $pipeline = app(Pipeline::class);

        return $pipeline->send($value)
            ->through($normalizers)
            ->thenReturn();
    }

    public function getQueryMethod(Visualizable $visualizable, FilterSetOperator $filterSetOperator): string
    {
        $queryMethod = $visualizable->isHavingRequired()
            ? 'havingRaw'
            : 'whereRaw';

        if ($filterSetOperator === FilterSetOperator::OR) {
            return 'or'.ucfirst($queryMethod);
        }

        return $queryMethod;
    }
}
