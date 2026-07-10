<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Modules\Agency\Services\AgencyPresenter;
use App\Modules\Agency\Services\AgencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends ApiController
{
    public function __construct(
        private readonly AgencyService $agencies,
        private readonly AgencyPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('agency.view'), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString() ?: 'all',
            'city' => $request->string('city')->toString() ?: 'all',
        ];

        return $this->paginateCollection(
            $this->agencies->filter($filters),
            $request,
            fn ($agency) => $this->presenter->indexRow($agency),
            'Acenteler listelendi',
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('agency.view'), 403);

        $agency = $this->agencies->find($id);

        if ($agency === null) {
            return ApiResponse::error('Acente bulunamadı.', 404);
        }

        return ApiResponse::success(
            $this->presenter->detailPayload($agency),
            'Acente detayı',
        );
    }
}
