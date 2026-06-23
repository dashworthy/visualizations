<?php

namespace Dashworthy\Visualizations\DataGrids\Traits;

use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridBulkActionRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridInlineActionRequest;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

trait HandlesDataGridActions
{
    /**
     * Defines a Collection of Actions that will be displayed outside the data grid
     *
     * @return Collection<int, Action>
     */
    public function getBulkActions(): Collection
    {
        return collect();
    }

    /**
     * Defines a Collection of Actions on each row of the data grid
     *
     * @return Collection<int, Action>
     */
    public function getInlineActions(): Collection
    {
        return collect();
    }

    /**
     * Handles the API request to execute an inline action against the grid.
     */
    public function handleInlineAction(DataGridInlineActionRequest $request, string $action): Response
    {
        $resolved = $this->getInlineActions()->first(
            fn (Action $candidate): bool => $candidate->getSlug() === $action
        );

        abort_unless($resolved !== null, 404, 'Action not found');

        abort_unless(
            $resolved->isAuthorized($request),
            403,
            'Unauthorized action: '.$resolved->name
        );

        $result = $resolved->handle(Collection::wrap($request->input('row_key')));

        if ($result instanceof Response) {
            return $result;
        }

        return response()->json($result);
    }

    /**
     * Handles the API request to execute a bulk action against the grid.
     */
    public function handleBulkAction(DataGridBulkActionRequest $request, string $action): Response
    {
        $resolved = $this->getBulkActions()->first(
            fn (Action $candidate): bool => $candidate->getSlug() === $action
        );

        abort_unless($resolved !== null, 404, 'Action not found');

        abort_unless(
            $resolved->isAuthorized($request),
            403,
            'Unauthorized action: '.$resolved->name
        );

        $result = $resolved->handle(Collection::wrap($request->input('row_keys', [])));

        if ($result instanceof Response) {
            return $result;
        }

        return response()->json($result);
    }
}
