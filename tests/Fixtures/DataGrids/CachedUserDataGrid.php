<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\ShouldCache;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\Traits\Cacheable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CachedUserDataGrid extends DataGrid implements ShouldCache
{
    use Cacheable;

    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')->asRowKey(),
            Text::make('users.name', 'Name'),
            Text::make('users.email', 'Email'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }

    public function getDefaultSorts(): Collection
    {
        return collect([
            SortData::make('ID', SortOperator::ASC),
        ]);
    }
}
