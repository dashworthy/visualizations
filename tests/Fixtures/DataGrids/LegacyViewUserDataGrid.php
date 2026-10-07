<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A user grid defining all six view handlers without implementing HandlesDataGridViews, so only the
 * three deprecated method-detected hooks (index, store, destroy) are registered.
 */
class LegacyViewUserDataGrid extends UserDataGrid
{
    public function handleViews(Request $request): JsonResponse
    {
        return new JsonResponse([]);
    }

    public function handleViewStore(Request $request): JsonResponse
    {
        return new JsonResponse([], 201);
    }

    public function handleViewUpdate(Request $request, string $view): JsonResponse
    {
        return new JsonResponse([]);
    }

    public function handleViewDestroy(Request $request, string $view): Response
    {
        return new Response(status: 204);
    }

    public function handleViewDefault(Request $request, string $view): Response
    {
        return new Response(status: 204);
    }

    public function handleViewClearDefault(Request $request, string $view): Response
    {
        return new Response(status: 204);
    }
}
