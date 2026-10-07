<?php

namespace Dashworthy\Visualizations\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A DataGrid that serves saved views. `Route::dataGrid()` registers all six views routes for a grid implementing this.
 *
 * Each handler is dispatched as a controller action and takes only the request. Read the view id by
 * name with `$request->route('view')`, never from a method parameter: Laravel passes route parameters
 * to an action by position, so when `Route::dataGrid()` sits inside a group with its own parameter
 * (`Route::prefix('t/{tenant}')`), a `$view` argument would receive the tenant instead of the view.
 *
 * The id is the raw route segment — never model-bound. This package holds no views storage, owner or
 * tenant context: the implementor resolves the view from that id and must scope it to the current
 * viewer and grid itself.
 *
 * Handlers that return a view or views return a JsonResponse. The rest return a Symfony Response so an
 * implementor may answer 204 No Content, or narrow the return type to JsonResponse to send a body.
 */
interface HandlesDataGridViews
{
    /** GET {path}/views — lists the views the viewer may apply to this grid. */
    public function handleViews(Request $request): JsonResponse;

    /** POST {path}/views — saves a new view. */
    public function handleViewStore(Request $request): JsonResponse;

    /** PATCH {path}/views/{view} — updates the saved view `$request->route('view')`. */
    public function handleViewUpdate(Request $request): JsonResponse;

    /** DELETE {path}/views/{view} — deletes the saved view `$request->route('view')`. */
    public function handleViewDestroy(Request $request): Response;

    /** PUT {path}/views/{view}/default — makes the saved view `$request->route('view')` the viewer's default for this grid. */
    public function handleViewDefault(Request $request): Response;

    /** DELETE {path}/views/{view}/default — clears the viewer's default view for this grid. */
    public function handleViewClearDefault(Request $request): Response;
}
