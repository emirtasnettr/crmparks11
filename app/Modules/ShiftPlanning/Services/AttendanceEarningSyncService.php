<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCommercialContract;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceEarningSyncService
{
    public const DESCRIPTION_PREFIX = '[vardiya-sync]';

    /**
     * Completed attendances with earnings → one draft/pending EarningLine per
     * (courier, business, month, pricing_model).
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
     * @param  array{courier_id?: int, business_id?: int, period_year?: int, period_month?: int}  $filters
     * @return Collection<string, Collection<int, BusinessShiftAttendance>>
     */
    private function groupedAttendances(array $filters): Collection
    {
        $query = BusinessShiftAttendance::query()
            ->where('status', 'completed')
            ->whereNotNull('earnings_amount')
            ->where('earnings_amount', '>', 0)
            ->whereNotNull('pricing_model');

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

        return $query->get()->groupBy(function (BusinessShiftAttendance $attendance): string {
            $year = $attendance->work_date?->format('Y') ?? '0';
            $month = $attendance->work_date?->format('n') ?? '0';

            return implode(':', [
                (string) $attendance->courier_id,
                (string) $attendance->business_id,
                $year,
                $month,
                (string) $attendance->pricing_model,
            ]);
        });
    }

    /**
     * @param  Collection<int, BusinessShiftAttendance>  $rows
     */
    private function syncGroup(Collection $rows, int $statusId, ?User $actor): string
    {
        /** @var BusinessShiftAttendance $sample */
        $sample = $rows->first();
        $pricingModel = (string) $sample->pricing_model;
        $periodMonth = (int) $sample->work_date->format('n');
        $periodYear = (int) $sample->work_date->format('Y');
        $courierId = (int) $sample->courier_id;
        $businessId = (int) $sample->business_id;

        $existing = EarningLine::query()
            ->with('status')
            ->where('courier_id', $courierId)
            ->where('business_id', $businessId)
            ->where('period_month', $periodMonth)
            ->where('period_year', $periodYear)
            ->where('pricing_model', $pricingModel)
            ->where('description', 'like', self::DESCRIPTION_PREFIX.'%')
            ->first();

        if ($existing !== null) {
            $code = $existing->status?->code
                ?? EarningStatus::query()->whereKey($existing->status_id)->value('code');

            if (! in_array($code, ['draft', 'pending_review'], true)) {
                return 'skipped';
            }
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
            'work_date' => $sample->work_date->toDateString(),
            'period_month' => $periodMonth,
            'period_year' => $periodYear,
            'description' => $description,
            'status_id' => $statusId,
            'extra_payment' => 0,
            'extra_expense' => 0,
            'deduction' => 0,
            'agency_payment' => 0,
        ]);

        return DB::transaction(function () use ($existing, $payload, $actor): string {
            if ($existing !== null) {
                $existing->update($payload);

                return 'updated';
            }

            $payload['created_by'] = $actor?->id;

            EarningLine::query()->create($payload);

            return 'created';
        });
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

        // per_package (+ guarantee fee on attendance)
        $courierUnit = round((float) ($contract?->courier_amount ?? 0), 2);
        $revenueUnit = round((float) ($contract?->business_amount ?? 0), 2);
        $avgRate = round((float) ($rows->avg('hourly_rate') ?: 0), 2);

        $packagesPerHour = ($courierUnit > 0 && $avgRate > 0)
            ? round($avgRate / $courierUnit, 4)
            : 0.0;
        $packageCount = $packagesPerHour > 0
            ? (int) max(1, (int) round($workedHours * $packagesPerHour))
            : ($courierUnit > 0 ? (int) max(1, (int) round($courierTotal / $courierUnit)) : 0);

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
