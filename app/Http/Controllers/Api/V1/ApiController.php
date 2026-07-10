<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class ApiController extends Controller
{
    /**
     * @param  Collection<int, mixed>  $items
     * @param  callable(mixed): array<string, mixed>  $mapper
     */
    protected function paginateCollection(
        Collection $items,
        Request $request,
        callable $mapper,
        string $message = 'OK',
    ): JsonResponse {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $total = $items->count();

        $data = $items
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->map($mapper)
            ->all();

        return ApiResponse::paginated($data, $total, $page, $perPage, $message);
    }
}
