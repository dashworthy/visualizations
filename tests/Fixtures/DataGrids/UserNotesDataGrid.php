<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A grid whose Notes column comes from a table the query never joins.
 *
 * Exports are the consuming app's job, not the package's — so this fixture defines handleExport() to
 * prove an export through DataGrid::hydrate() ships what the grid shows.
 */
class UserNotesDataGrid extends DataGrid
{
    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')->asRowKey(),
            Text::make('users.name', 'Name'),
            HydratedColumn::for(UserNotesHydrator::class, 'Notes'),
        ]);
    }

    public function getQuery(): Builder
    {
        // Ordered, so the tests' positional assertions do not lean on sqlite's scan order.
        return DB::table('users')->orderBy('users.id');
    }

    public function handleExport(DataGridDataRequest $request): JsonResponse
    {
        $query = GenerateVisualizationQuery::make()->handle(
            $this->getQuery(),
            $this->getVisualizables(),
            VisualizationData::fromDataGridRequest($request),
        );

        return response()->json(['data' => $this->hydrate($query->get())]);
    }
}
