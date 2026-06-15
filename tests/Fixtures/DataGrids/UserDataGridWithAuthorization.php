<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Traits\HasVisualizationPermissions;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserDataGridWithAuthorization extends DataGrid
{
    use HasVisualizationPermissions;

    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID'),
            Text::make('users.name', 'Name'),
            Text::make('users.email', 'Email'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }
}
