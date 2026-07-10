<?php

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Search\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $search,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->string('q')->toString();

        $result = $this->search->search($request->user(), $query);

        return ApiResponse::success($result);
    }
}
