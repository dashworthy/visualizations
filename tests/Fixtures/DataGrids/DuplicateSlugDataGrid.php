<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DuplicateSlugDataGrid extends DataGrid
{
    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')->asRowKey(),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }

    public function getInlineActions(): Collection
    {
        return collect([
            Action::make('Disable', fn (): null => null),
            // Different display name, same slug -> collision.
            Action::make('Disable', fn (): null => null)->slug('disable'),
        ]);
    }
}
