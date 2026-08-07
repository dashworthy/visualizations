<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\Tests\Fixtures\CustomVisualizationType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A visualization reporting a kind this package does not define, built the way
 * an application would: extend a base class for the machinery, override
 * `getVisualizationType()` to return its own type.
 *
 * The override is what forces the base classes to declare the contract as
 * their return type rather than narrowing to `VisualizationType`. Narrowing
 * would be legal covariance and would read as more precise, but PHP forbids
 * widening a return type in an override, so it would make this class a fatal
 * error and close the extension point to anything extending a base class.
 */
class FunnelDataGrid extends DataGrid
{
    public function getVisualizationType(): DefinesVisualizationType
    {
        return CustomVisualizationType::Funnel;
    }

    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')
                ->asRowKey(),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }
}
