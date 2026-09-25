<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A grid over whatever columns and floating filters a test hands it.
 *
 * Built through with() because DataGrid's constructor is final.
 */
class DeclaredColumnsDataGrid extends DataGrid
{
    /** @var Collection<int, Column> */
    protected Collection $declaredColumns;

    /** @var Collection<int, FloatingFilter> */
    protected Collection $declaredFloatingFilters;

    /**
     * @param  Collection<int, Column>  $columns
     * @param  Collection<int, FloatingFilter>|null  $floatingFilters
     */
    public static function with(Collection $columns, ?Collection $floatingFilters = null): self
    {
        $grid = new self;
        $grid->declaredColumns = $columns;
        $grid->declaredFloatingFilters = $floatingFilters ?? collect();

        return $grid;
    }

    public function getColumns(): Collection
    {
        return $this->declaredColumns;
    }

    public function getFloatingFilters(): Collection
    {
        return $this->declaredFloatingFilters;
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }
}
