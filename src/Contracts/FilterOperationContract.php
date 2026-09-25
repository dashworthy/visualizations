<?php

namespace Dashworthy\Visualizations\Contracts;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Database\Query\Builder;

/**
 * Turns a filter into a clause on the visualization's query.
 *
 * The package binds this to `Query\MariaDbFilterOperation`, which writes MariaDB
 * SQL. An application that needs a different database, different SQL for an
 * operator, or operators of its own binds its own implementation instead —
 * usually a subclass of `MariaDbFilterOperation` — and
 * `GenerateVisualizationQuery` and `FilterSetRule` pick it up.
 *
 * `operators()` is the set of keys a filter may send. Implementors own any
 * keys they add and are responsible for keeping them distinct from the ones
 * `FilterOperator` ships.
 */
interface FilterOperationContract
{
    /**
     * Every operator key this implementation can apply.
     *
     * @return list<string>
     */
    public function operators(): array;

    public function handle(Builder $query, Visualizable $visualizable, FilterData $filterData, FilterSetOperator $filterSetOperator = FilterSetOperator::AND): Builder;
}
