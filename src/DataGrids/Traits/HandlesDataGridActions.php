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
     * Handles the API request to execute an action against the grid.
     */
    public function handleInlineAction(DataGridInlineActionRequest $request): Response
    {
        $action = $this->getInlineActions()
            ->firstWhere(
                'name',
                $request->input('action')
            );

        /**
         * Check if the action is an instance of the Action class and return a 404 if it is not
         */
        abort_unless(
            ! is_null($action),
            404,
            'Action not found'
        );

        /**
         * Check if the action is authorized to be executed
         */
        abort_unless(
            $action->isAuthorized($request),
            403,
            'Unauthorized action: '.$action->name
        );

        $result = $action->handle($this, Collection::wrap($request->input('row_key')));

        if ($result instanceof Response) {
            return $result;
        }

        return response()->json($result);
    }

    /**
     * Handles the API request to execute an action against the grid.
     */
    public function handleBulkAction(DataGridBulkActionRequest $request): Response
    {
        $action = $this->getBulkActions()
            ->firstWhere(
                'name',
                $request->input('action')
            );

        /**
         * Check if the action is an instance of the Action class and return a 404 if it is not
         */
        abort_unless(
            ! is_null($action),
            404,
            'Action not found'
        );

        /**
         * Check if the action is authorized to be executed
         */
        abort_unless(
            $action->isAuthorized($request),
            403,
            'Unauthorized action: '.$action->name
        );

        $result = $action->handle($this, Collection::wrap($request->input('row_keys', [])));

        if ($result instanceof Response) {
            return $result;
        }

        return response()->json($result);
    }
}
