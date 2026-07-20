<?php

namespace App\Modules\Business\Services;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCommercialContract;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessCommercialContractService
{
    public function __construct(
        private readonly BusinessCommercialContractPresenter $presenter,
    ) {}

    /**
     * @return Collection<int, BusinessCommercialContract>
     */
    public function forBusiness(int $businessId): Collection
    {
        return BusinessCommercialContract::query()
            ->where('business_id', $businessId)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): ?BusinessCommercialContract
    {
        return BusinessCommercialContract::query()->with('business')->find($id);
    }

    public function activeForBusiness(int $businessId): ?BusinessCommercialContract
    {
        return BusinessCommercialContract::query()
            ->where('business_id', $businessId)
            ->where('status', BusinessCommercialContract::STATUS_ACTIVE)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    public function forBusinessOnDate(int $businessId, CarbonInterface|string $date): ?BusinessCommercialContract
    {
        $day = Carbon::parse($date)->toDateString();

        return BusinessCommercialContract::query()
            ->where('business_id', $businessId)
            ->whereDate('start_date', '<=', $day)
            ->where(function ($query) use ($day): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $day);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): BusinessCommercialContract
    {
        $businessId = (int) $data['business_id'];
        $startDate = Carbon::parse((string) $data['start_date'])->startOfDay();
        $endDate = filled($data['end_date'] ?? null)
            ? Carbon::parse((string) $data['end_date'])->startOfDay()
            : null;

        if ($endDate !== null && $endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
            ]);
        }

        return DB::transaction(function () use ($data, $user, $businessId, $startDate, $endDate): BusinessCommercialContract {
            $active = $this->activeForBusiness($businessId);
            $supersedesId = null;

            if ($active !== null) {
                $supersedesId = $active->id;
                $closeOn = $startDate->copy()->subDay();

                if ($closeOn->lt($active->start_date->copy()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'start_date' => 'Yeni kontrat, mevcut kontratın başlangıcından önce başlayamaz. Mevcut: '.$active->start_date->format('d.m.Y'),
                    ]);
                }

                $active->update([
                    'end_date' => $closeOn->toDateString(),
                    'status' => BusinessCommercialContract::STATUS_ENDED,
                ]);
            }

            $businessAmount = round((float) $data['business_amount'], 2);
            $courierAmount = round((float) $data['courier_amount'], 2);
            $workType = (string) $data['work_type'];
            $guarantee = $workType === BusinessCommercialContract::WORK_PER_PACKAGE
                && filled($data['guaranteed_hourly_package_fee'] ?? null)
                ? round((float) $data['guaranteed_hourly_package_fee'], 2)
                : null;

            return BusinessCommercialContract::query()->create([
                'business_id' => $businessId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate?->toDateString(),
                'work_type' => $workType,
                'business_amount' => $businessAmount,
                'courier_amount' => $courierAmount,
                'net_profit' => round($businessAmount - $courierAmount, 2),
                'guaranteed_hourly_package_fee' => $guarantee,
                'payment_period' => (string) $data['payment_period'],
                'status' => BusinessCommercialContract::STATUS_ACTIVE,
                'supersedes_id' => $supersedesId,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * Aktif kontratın tutar alanları değiştirilemez; yalnızca bitiş/not güncellenir.
     * Tutar değişimi için yeni kontrat oluşturulmalıdır.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(BusinessCommercialContract $contract, array $data): BusinessCommercialContract
    {
        if (! $contract->isActive()) {
            throw ValidationException::withMessages([
                'contract' => 'Sona ermiş kontratlar değiştirilemez. Geçmiş kayıtlar korunur.',
            ]);
        }

        $endDate = filled($data['end_date'] ?? null)
            ? Carbon::parse((string) $data['end_date'])->startOfDay()
            : null;

        if ($endDate !== null && $endDate->lt($contract->start_date->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'end_date' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
            ]);
        }

        $contract->update([
            'end_date' => $endDate?->toDateString(),
            'notes' => $data['notes'] ?? $contract->notes,
        ]);

        return $contract->fresh();
    }

    public function end(BusinessCommercialContract $contract, ?CarbonInterface $endedOn = null): BusinessCommercialContract
    {
        if (! $contract->isActive()) {
            return $contract;
        }

        $day = $endedOn
            ? Carbon::parse($endedOn)->startOfDay()
            : now()->startOfDay();

        if ($day->lt($contract->start_date->copy()->startOfDay())) {
            $day = $contract->start_date->copy()->startOfDay();
        }

        $contract->update([
            'end_date' => $day->toDateString(),
            'status' => BusinessCommercialContract::STATUS_ENDED,
        ]);

        return $contract->fresh();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->displayName(),
            ])
            ->all();
    }
}
