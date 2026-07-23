<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCommercialContract;
use App\Modules\Business\Services\BusinessCommercialContractService;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceEarningSyncService
{
    public const DESCRIPTION_PREFIX = '[vardiya-sync]';

    public function __construct(
        private readonly BusinessCommercialContractService $commercialContracts,
    ) {}

    /**
     * Completed attendances → one draft/pending EarningLine per
     * (courier, business, work_date, pricing_model).
     *
     * @param  array{courier_id?: int, business_id?: int, period_year?: int, period_month?: int}  $filters
     * @return array{created: int, updated: int, skipped: int}
     */
    public function sync(?User $actor = null, array $filters = []): array
    {
        $statusId = EarningStatus::query()->where('code', 'pending_review')->value('id')
            ?? EarningStatus::query()->where('code', 'draft')->value('id');

        if ($statusId === null) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $this->backfillMissingEarningsFromContract($filters);

        $groups = $this->groupedAttendances($filters);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($groups as $rows) {
            $result = $this->syncGroup($rows, $statusId, $actor);
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * Aynı gün için oluşmuş [vardiya-sync] kopyalarını temizler (en yeni satır kalır).
     *
     * @return array{removed: int, groups: int}
     */
    public function dedupeSyncLines(): array
    {
        $editableStatusIds = EarningStatus::query()
            ->whereIn('code', ['draft', 'pending_review'])
            ->pluck('id')
            ->all();

        if ($editableStatusIds === []) {
            return ['removed' => 0, 'groups' => 0];
        }

        $duplicateKeys = EarningLine::query()
            ->select(['courier_id', 'business_id', 'work_date', 'pricing_model'])
            ->where('description', 'like', self::DESCRIPTION_PREFIX.'%')
            ->whereIn('status_id', $editableStatusIds)
            ->whereNotNull('work_date')
            ->groupBy('courier_id', 'business_id', 'work_date', 'pricing_model')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $removed = 0;

        foreach ($duplicateKeys as $key) {
            $lines = EarningLine::query()
                ->where('courier_id', $key->courier_id)
                ->where('business_id', $key->business_id)
                ->whereDate('work_date', $key->work_date)
                ->where('pricing_model', $key->pricing_model)
                ->where('description', 'like', self::DESCRIPTION_PREFIX.'%')
                ->whereIn('status_id', $editableStatusIds)
                ->orderByDesc('id')
                ->get();

            $keep = $lines->shift();
            if ($keep === null) {
                continue;
            }

            foreach ($lines as $duplicate) {
                $duplicate->delete();
                $removed++;
            }
        }

        return ['removed' => $removed, 'groups' => $duplicateKeys->count()];
    }

    /**
     * Kontratta saatlik / garanti ücret varsa, tutarı boş kalan tamamlanmış
     * katılımları senkron öncesi doldurur.
     *
     * @param  array{courier_id?: int, business_id?: int, period_year?: int, period_month?: int}  $filters
     */
    private function backfillMissingEarningsFromContract(array $filters): void
    {
        $query = BusinessShiftAttendance::query()
            ->where('status', 'completed')
            ->where('worked_minutes', '>', 0)
            ->where(function ($q): void {
                $q->whereNull('earnings_amount')
                    ->orWhere('earnings_amount', '<=', 0)
                    ->orWhereNull('hourly_rate')
                    ->orWhereNull('pricing_model');
            });

        $this->applyAttendanceFilters($query, $filters);

        foreach ($query->get() as $attendance) {
            $day = $attendance->work_date;
            if ($day === null) {
                continue;
            }

            if ($this->isBusinessOrCourierInactive((int) $attendance->business_id, (int) $attendance->courier_id)) {
                continue;
            }

            $contract = $attendance->commercial_contract_id
                ? BusinessCommercialContract::query()->find((int) $attendance->commercial_contract_id)
                : $this->commercialContracts->forBusinessOnDate((int) $attendance->business_id, $day);

            if ($contract === null) {
                continue;
            }

            $hourlyRate = $contract->courierHourlyRateForAttendance();
            if ($hourlyRate === null || $hourlyRate <= 0) {
                continue;
            }

            $minutes = (int) $attendance->worked_minutes;
            $attendance->update([
                'commercial_contract_id' => $attendance->commercial_contract_id ?: $contract->id,
                'pricing_model' => $attendance->pricing_model ?: $contract->work_type,
                'hourly_rate' => $hourlyRate,
                'earnings_amount' => round(($minutes / 60) * $hourlyRate, 2),
            ]);
        }
    }

    /**
     * @param  array{courier_id?: int, business_id?: int, period_year?: int, period_month?: int}  $filters
     * @return Collection<string, Collection<int, BusinessShiftAttendance>>
     */
    private function groupedAttendances(array $filters): Collection
    {
        $query = BusinessShiftAttendance::query()
            ->where('status', 'completed')
            ->whereNotNull('earnings_amount')
            ->where('earnings_amount', '>', 0)
            ->whereNotNull('pricing_model')
            ->whereNotNull('work_date');

        $this->applyAttendanceFilters($query, $filters);

        return $query->orderBy('id')->get()->groupBy(function (BusinessShiftAttendance $attendance): string {
            return implode(':', [
                (string) $attendance->courier_id,
                (string) $attendance->business_id,
                $attendance->work_date->toDateString(),
                (string) $attendance->pricing_model,
            ]);
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Modules\ShiftPlanning\Models\BusinessShiftAttendance>  $query
     * @param  array{courier_id?: int, business_id?: int, period_year?: int, period_month?: int}  $filters
     */
    private function applyAttendanceFilters($query, array $filters): void
    {
        if (! empty($filters['courier_id'])) {
            $query->where('courier_id', (int) $filters['courier_id']);
        }
        if (! empty($filters['business_id'])) {
            $query->where('business_id', (int) $filters['business_id']);
        }
        if (! empty($filters['period_year'])) {
            $query->whereYear('work_date', (int) $filters['period_year']);
        }
        if (! empty($filters['period_month'])) {
            $query->whereMonth('work_date', (int) $filters['period_month']);
        }
    }

    /**
     * @param  Collection<int, BusinessShiftAttendance>  $rows
     */
    private function syncGroup(Collection $rows, int $statusId, ?User $actor): string
    {
        /** @var BusinessShiftAttendance $sample */
        $sample = $rows->first();
        $pricingModel = (string) $sample->pricing_model;
        $workDate = $sample->work_date->toDateString();
        $periodMonth = (int) $sample->work_date->format('n');
        $periodYear = (int) $sample->work_date->format('Y');
        $courierId = (int) $sample->courier_id;
        $businessId = (int) $sample->business_id;

        if ($this->isBusinessOrCourierInactive($businessId, $courierId)) {
            return 'skipped';
        }

        $amounts = $this->amountsFromAttendances($rows, $pricingModel, $businessId);
        if ($amounts === null) {
            return 'skipped';
        }

        $workedHours = round($rows->sum('worked_minutes') / 60, 2);
        $description = self::DESCRIPTION_PREFIX.' '
            .($pricingModel === 'hourly' ? 'Saatlik' : 'Paket başı')
            ." vardiya hakedişi ({$workedHours} sa)";

        $payload = array_merge($amounts, [
            'business_id' => $businessId,
            'courier_id' => $courierId,
            'business_pricing_id' => null,
            'pricing_model' => $pricingModel,
            'work_date' => $workDate,
            'period_month' => $periodMonth,
            'period_year' => $periodYear,
            'description' => $description,
            'status_id' => $statusId,
            'extra_payment' => 0,
            'extra_expense' => 0,
            'deduction' => 0,
            'agency_payment' => 0,
        ]);

        return DB::transaction(function () use ($payload, $actor, $courierId, $businessId, $workDate, $pricingModel): string {
            $existing = EarningLine::query()
                ->with('status')
                ->where('courier_id', $courierId)
                ->where('business_id', $businessId)
                ->whereDate('work_date', $workDate)
                ->where('pricing_model', $pricingModel)
                ->where('description', 'like', self::DESCRIPTION_PREFIX.'%')
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if ($existing !== null) {
                $code = $existing->status?->code
                    ?? EarningStatus::query()->whereKey($existing->status_id)->value('code');

                if (! in_array($code, ['draft', 'pending_review'], true)) {
                    return 'skipped';
                }

                $existing->update($payload);

                // Aynı gün için kalan kopyaları temizle.
                EarningLine::query()
                    ->where('courier_id', $courierId)
                    ->where('business_id', $businessId)
                    ->whereDate('work_date', $workDate)
                    ->where('pricing_model', $pricingModel)
                    ->where('description', 'like', self::DESCRIPTION_PREFIX.'%')
                    ->where('id', '!=', $existing->id)
                    ->whereHas('status', fn ($q) => $q->whereIn('code', ['draft', 'pending_review']))
                    ->get()
                    ->each(fn (EarningLine $line) => $line->delete());

                return 'updated';
            }

            $payload['created_by'] = $actor?->id;
            EarningLine::query()->create($payload);

            return 'created';
        });
    }

    private function isBusinessOrCourierInactive(int $businessId, int $courierId): bool
    {
        $businessInactive = Business::query()
            ->whereKey($businessId)
            ->where('status', 'inactive')
            ->exists();

        if ($businessInactive) {
            return true;
        }

        return \App\Modules\Courier\Models\Courier::query()
            ->whereKey($courierId)
            ->where('status', 'inactive')
            ->exists();
    }

    /**
     * @param  Collection<int, BusinessShiftAttendance>  $rows
     * @return array<string, mixed>|null
     */
    private function amountsFromAttendances(Collection $rows, string $pricingModel, int $businessId): ?array
    {
        $workedHours = round($rows->sum('worked_minutes') / 60, 2);
        $courierTotal = round((float) $rows->sum('earnings_amount'), 2);
        if ($courierTotal <= 0 || $workedHours <= 0) {
            return null;
        }

        $business = Business::query()->with('activeCommercialContract')->find($businessId);
        $contract = $this->resolveContract($rows, $business);

        if ($pricingModel === 'hourly') {
            $courierUnit = round((float) ($rows->avg('hourly_rate') ?: ($contract?->courier_amount ?? 0)), 2);
            $revenueUnit = round((float) ($contract?->business_amount ?? ($courierUnit * 1.5)), 2);
            $revenueTotal = round($workedHours * $revenueUnit, 2);

            return [
                'earning_type' => 'hourly',
                'package_count' => 0,
                'worked_hours' => $workedHours,
                'revenue_unit_price' => $revenueUnit,
                'revenue_total' => $revenueTotal,
                'courier_unit_price' => $courierUnit,
                'courier_total' => $courierTotal,
                'net_courier_payment' => $courierTotal,
                'profit' => round($revenueTotal - $courierTotal, 2),
            ];
        }

        $courierUnit = round((float) ($contract?->courier_amount ?? 0), 2);
        $revenueUnit = round((float) ($contract?->business_amount ?? 0), 2);
        $packageCount = (float) $rows->sum(fn (BusinessShiftAttendance $row) => (float) ($row->package_count ?? 0));

        if ($packageCount <= 0) {
            $avgRate = round((float) ($rows->avg('hourly_rate') ?: 0), 2);
            $packagesPerHour = ($courierUnit > 0 && $avgRate > 0)
                ? round($avgRate / $courierUnit, 4)
                : 0.0;
            $packageCount = $packagesPerHour > 0
                ? round(max(0.01, $workedHours * $packagesPerHour), 2)
                : ($courierUnit > 0 ? round(max(0.01, $courierTotal / $courierUnit), 2) : 0);
        }

        $revenueTotal = round($packageCount * $revenueUnit, 2);

        return [
            'earning_type' => 'package_based',
            'package_count' => $packageCount,
            'worked_hours' => $workedHours,
            'revenue_unit_price' => $revenueUnit,
            'revenue_total' => $revenueTotal,
            'courier_unit_price' => $courierUnit,
            'courier_total' => $courierTotal,
            'net_courier_payment' => $courierTotal,
            'profit' => round($revenueTotal - $courierTotal, 2),
        ];
    }

    /**
     * @param  Collection<int, BusinessShiftAttendance>  $rows
     */
    private function resolveContract(Collection $rows, ?Business $business): ?BusinessCommercialContract
    {
        $contractId = $rows->pluck('commercial_contract_id')->filter()->first();
        if ($contractId !== null) {
            $contract = BusinessCommercialContract::query()->find((int) $contractId);
            if ($contract !== null) {
                return $contract;
            }
        }

        return $business?->activeCommercialContract;
    }
}
