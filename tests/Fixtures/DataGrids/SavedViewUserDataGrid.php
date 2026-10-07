<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\HandlesDataGridViews;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A user grid implementing the saved-views contract, so the dataGrid route macro registers every views route.
 *
 * Each handler echoes the route parameters it read by name, so a test can see which segment reached it.
 */
class SavedViewUserDataGrid extends UserDataGrid implements HandlesDataGridViews
{
    public function handleViews(Request $request): JsonResponse
    {
        return $this->echoRoute($request, __FUNCTION__);
    }

    public function handleViewStore(Request $request): JsonResponse
    {
        return $this->echoRoute($request, __FUNCTION__);
    }

    public function handleViewUpdate(Request $request): JsonResponse
    {
        return $this->echoRoute($request, __FUNCTION__);
    }

    public function handleViewDestroy(Request $request): JsonResponse
    {
        return $this->echoRoute($request, __FUNCTION__);
    }

    public function handleViewDefault(Request $request): JsonResponse
    {
        return $this->echoRoute($request, __FUNCTION__);
    }

    public function handleViewClearDefault(Request $request): JsonResponse
    {
        return $this->echoRoute($request, __FUNCTION__);
    }

    private function echoRoute(Request $request, string $handler): JsonResponse
    {
        return new JsonResponse([
            'handler' => $handler,
            'tenant' => $request->route('tenant'),
            'view' => $request->route('view'),
        ]);
    }
}
