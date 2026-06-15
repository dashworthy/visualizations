<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserDataGrid extends DataGrid
{
    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')
                ->asRowKey(),
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

    public function getInlineActions(): Collection
    {
        return collect([
            Action::make('Edit', fn (): array => [
                'ran' => true,
            ])->withAuthorization('edit-users'),
        ]);
    }

    public function getBulkActions(): Collection
    {
        return collect([
            Action::make('Create', fn (): array => [
                'ran' => true,
            ])->withAuthorization('create-users'),
        ]);
    }

    public function getFloatingFilters(): Collection
    {
        return collect([
            DateRange::make('DATE(users.created_at)', 'Joined On'),
        ]);
    }
}
