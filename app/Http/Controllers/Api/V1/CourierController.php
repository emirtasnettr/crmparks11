<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Modules\Courier\Requests\StoreCourierRequest;
use App\Modules\Courier\Requests\UpdateCourierRequest;
use App\Modules\Courier\Services\CourierPresenter;
use App\Modules\Courier\Services\CourierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController extends ApiController
{
    public function __construct(
        private readonly CourierService $couriers,
        private readonly CourierPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('courier.view'), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
        ];

        return $this->paginateCollection(
            $this->couriers->filter($filters),
            $request,
            fn ($courier) => $this->presenter->indexRow($courier),
            'Kuryeler listelendi',
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('courier.view'), 403);

        $courier = $this->couriers->find($id);

        if ($courier === null) {
            return ApiResponse::error('Kurye bulunamadı.', 404);
        }

        return ApiResponse::success(
            $this->presenter->detailPayload($courier),
            'Kurye detayı',
        );
    }

    public function store(StoreCourierRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $courier = $this->couriers->create($data, $request->user());

        return ApiResponse::success(
            $this->presenter->detailPayload($courier),
            'Kurye oluşturuldu',
            201,
        );
    }

    public function update(UpdateCourierRequest $request, int $id): JsonResponse
    {
        $courier = $this->couriers->find($id);

        if ($courier === null) {
            return ApiResponse::error('Kurye bulunamadı.', 404);
        }

        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $courier = $this->couriers->update($courier, $data, $request->user());

        return ApiResponse::success(
            $this->presenter->detailPayload($courier),
            'Kurye güncellendi',
        );
    }
}
