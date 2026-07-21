<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Modules\Business\Requests\ApproveBusinessEarningRequest;
use App\Modules\Business\Requests\StoreBusinessEarningRequest;
use App\Modules\Business\Services\BusinessEarningPresenter;
use App\Modules\Business\Services\BusinessEarningService;
use App\Support\EarningListDateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningController extends ApiController
{
    public function __construct(
        private readonly BusinessEarningService $earnings,
        private readonly BusinessEarningPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('earning.view'), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            ...EarningListDateRange::fromRequest($request),
        ];

        return $this->paginateCollection(
            $this->earnings->filter($filters),
            $request,
            fn ($line) => $this->presenter->indexRow($line),
            'Hakedişler listelendi',
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('earning.view'), 403);

        $line = $this->earnings->find($id);

        if ($line === null) {
            return ApiResponse::error('Hakediş bulunamadı.', 404);
        }

        return ApiResponse::success(
            $this->presenter->showRow($line),
            'Hakediş detayı',
        );
    }

    public function store(StoreBusinessEarningRequest $request): JsonResponse
    {
        $line = $this->earnings->create($request->validated(), $request->user());

        return ApiResponse::success(
            $this->presenter->showRow($line),
            'Hakediş oluşturuldu',
            201,
        );
    }

    public function approve(ApproveBusinessEarningRequest $request, int $id): JsonResponse
    {
        $line = $this->earnings->approve($id, $request->user());

        $message = $line->status?->code === 'approved'
            ? 'Hakediş onaylandı'
            : 'Hakediş ilk onayı alındı';

        return ApiResponse::success(
            $this->presenter->showRow($line),
            $message,
        );
    }
}
