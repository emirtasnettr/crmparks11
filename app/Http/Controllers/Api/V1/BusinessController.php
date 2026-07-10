<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Modules\Business\Requests\StoreBusinessRequest;
use App\Modules\Business\Requests\UpdateBusinessRequest;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Business\Services\BusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends ApiController
{
    public function __construct(
        private readonly BusinessService $businesses,
        private readonly BusinessPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('business.view'), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString() ?: 'all',
            'city' => $request->string('city')->toString() ?: 'all',
            'pricing_model' => $request->string('pricing_model')->toString() ?: 'all',
        ];

        return $this->paginateCollection(
            $this->businesses->filter($filters),
            $request,
            fn ($business) => $this->presenter->indexRow($business),
            'İşletmeler listelendi',
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('business.view'), 403);

        $business = $this->businesses->find($id);

        if ($business === null) {
            return ApiResponse::error('İşletme bulunamadı.', 404);
        }

        return ApiResponse::success(
            $this->presenter->detailPayload($business),
            'İşletme detayı',
        );
    }

    public function store(StoreBusinessRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $business = $this->businesses->create($data, $request->user());

        return ApiResponse::success(
            $this->presenter->detailPayload($business),
            'İşletme oluşturuldu',
            201,
        );
    }

    public function update(UpdateBusinessRequest $request, int $id): JsonResponse
    {
        $business = $this->businesses->find($id);

        if ($business === null) {
            return ApiResponse::error('İşletme bulunamadı.', 404);
        }

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $business = $this->businesses->update($business, $data, $request->user());

        return ApiResponse::success(
            $this->presenter->detailPayload($business),
            'İşletme güncellendi',
        );
    }
}
