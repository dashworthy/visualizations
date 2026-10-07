<?php

namespace Dashworthy\Visualizations\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A DataGrid that serves saved views. `Route::dataGrid()` registers all six views routes for a grid implementing this.
 *
 * Each handler is dispatched as a controller action, so `$request` is resolved from the container and
 * `$view` is the raw `{view}` route segment — never model-bound. This package holds no views storage,
 * owner or tenant context: the implementor resolves the view from that id and must scope it to the
 * current viewer and grid itself.
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

    /** PATCH {path}/views/{view} — updates a saved view. */
    public function handleViewUpdate(Request $request, string $view): JsonResponse;

    /** DELETE {path}/views/{view} — deletes a saved view. */
    public function handleViewDestroy(Request $request, string $view): Response;

    /** PUT {path}/views/{view}/default — makes a saved view the viewer's default for this grid. */
    public function handleViewDefault(Request $request, string $view): Response;

    /** DELETE {path}/views/{view}/default — clears the viewer's default view for this grid. */
    public function handleViewClearDefault(Request $request, string $view): Response;
}
