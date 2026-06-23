<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserDataGridWithActionRules extends DataGrid
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
            Action::make('Edit', fn (): array => ['ran' => true])
                ->rules([Rule::exists('users', 'id')]),
            Action::make('NoRules', fn (): array => ['ran' => true]),
        ]);
    }

    public function getBulkActions(): Collection
    {
        return collect([
            Action::make('Delete', fn (): array => ['ran' => true])
                ->rules([Rule::exists('users', 'id')]),
        ]);
    }
}
