<?php

namespace App\Modules\Dashboard\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Courier\Data\CourierFormData;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Services\CourierPresenter;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly BusinessPresenter $businessPresenter,
        private readonly CourierPresenter $courierPresenter,
    ) {}

    public function getStats(): array
    {
        $totalCouriers = Courier::query()->count();
        $activeCouriers = Courier::query()->where('status', 'active')->count();

        return [
            'total_businesses' => Business::query()->count(),
            'total_couriers' => $totalCouriers,
            'total_agencies' => Agency::query()->count(),
            'active_couriers' => $activeCouriers,
            'inactive_couriers' => $totalCouriers - $activeCouriers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFinanceOverview(): array
    {
        $start = Carbon::today()->startOfMonth();
        $end = Carbon::today()->endOfMonth();

        $revenue = (float) FinanceRevenue::query()
            ->whereBetween('revenue_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $expense = (float) FinanceExpense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $profit = round($revenue - $expense, 2);

        $pendingCollectionRemaining = (float) (FinanceCollection::query()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - collected_amount) as remaining')
            ->value('remaining') ?? 0);

        $pendingPaymentRemaining = (float) (FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - paid_amount) as remaining')
            ->value('remaining') ?? 0);

        $pendingCollectionCount = FinanceCollection::query()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->count();

        $pendingPaymentCount = FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->count();

        $pendingEarningCount = $this->pendingEarningQuery()->count();

        return [
            'period_label' => $start->translatedFormat('F Y'),
            'revenue' => round($revenue, 2),
            'revenue_formatted' => MoneyCalculator::format($revenue),
            'expense' => round($expense, 2),
            'expense_formatted' => MoneyCalculator::format($expense),
            'net_profit' => $profit,
            'net_profit_formatted' => MoneyCalculator::format($profit),
            'pending_collection' => round($pendingCollectionRemaining, 2),
            'pending_collection_formatted' => MoneyCalculator::format($pendingCollectionRemaining),
            'pending_collection_count' => $pendingCollectionCount,
            'pending_payment' => round($pendingPaymentRemaining, 2),
            'pending_payment_formatted' => MoneyCalculator::format($pendingPaymentRemaining),
            'pending_payment_count' => $pendingPaymentCount,
            'pending_earning_count' => $pendingEarningCount,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPendingCollections(int $limit = 5): array
    {
        $today = Carbon::today();

        return FinanceCollection::query()
            ->with('business:id,company_name')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->limit($limit)
            ->get()
            ->map(function (FinanceCollection $collection) use ($today): array {
                $remaining = round((float) $collection->total_amount - (float) $collection->collected_amount, 2);
                $delay = (int) $today->diffInDays($collection->due_date, false);

                return [
                    'id' => $collection->id,
                    'business' => $collection->business?->company_name ?? '—',
                    'reference' => $collection->reference,
                    'due_date_formatted' => $collection->due_date->format('d.m.Y'),
                    'amount_formatted' => MoneyCalculator::format($remaining),
                    'is_overdue' => $delay < 0,
                    'delay_label' => $delay < 0
                        ? abs($delay).' gün gecikmiş'
                        : ($delay === 0 ? 'Bugün' : $delay.' gün kaldı'),
                    'url' => route('finance.collections.show', $collection->id),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPendingPayments(int $limit = 5): array
    {
        $today = Carbon::today();

        return FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('scheduled_date')
            ->limit($limit)
            ->get()
            ->map(function (FinancePayment $payment) use ($today): array {
                $remaining = round((float) $payment->total_amount - (float) $payment->paid_amount, 2);
                $delay = (int) $today->diffInDays($payment->scheduled_date, false);

                return [
                    'id' => $payment->id,
                    'recipient' => $payment->recipient_name ?? '—',
                    'reference' => $payment->reference,
                    'scheduled_date_formatted' => $payment->scheduled_date->format('d.m.Y'),
                    'amount_formatted' => MoneyCalculator::format($remaining),
                    'is_overdue' => $delay < 0,
                    'delay_label' => $delay < 0
                        ? abs($delay).' gün gecikmiş'
                        : ($delay === 0 ? 'Bugün' : $delay.' gün kaldı'),
                    'url' => route('finance.payments.show', $payment->id),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPendingEarnings(int $limit = 5): array
    {
        return $this->pendingEarningQuery()
            ->with(['business:id,company_name', 'courier:id,full_name', 'status'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (EarningLine $line): array {
                return [
                    'id' => $line->id,
                    'business' => $line->business?->company_name ?? '—',
                    'courier' => $line->courier?->full_name ?? '—',
                    'period' => sprintf('%02d/%d', $line->period_month, $line->period_year),
                    'revenue_formatted' => MoneyCalculator::format((float) $line->revenue_total),
                    'url' => route('businesses.earnings.show', $line->id),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatestBusinesses(int $limit = 5): array
    {
        return Business::query()
            ->with(['city', 'district', 'activePricing.pricingModelType'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Business $business) => $this->formatBusinessForDashboard(
                $this->businessPresenter->toBaseArray($business),
                $business,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatestCouriers(int $limit = 5): array
    {
        return Courier::query()
            ->with(['city', 'district', 'agency', 'vehicleType'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Courier $courier) => $this->formatCourierForDashboard(
                $this->courierPresenter->toBaseArray($courier),
                $courier,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    public function getCourierTypeDistribution(): array
    {
        $total = Courier::query()->count();

        $items = collect(CourierFormData::courierTypes())
            ->map(function (string $label, string $key) use ($total) {
                $count = Courier::query()->where('courier_type', $key)->count();

                return [
                    'key' => $key,
                    'label' => $label,
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<EarningLine>
     */
    private function pendingEarningQuery()
    {
        $statusId = EarningStatus::query()->where('code', 'pending_review')->value('id');

        return EarningLine::query()->when(
            $statusId !== null,
            fn ($query) => $query->where('status_id', $statusId),
            fn ($query) => $query->whereRaw('1 = 0'),
        );
    }

    /**
     * @param  array<string, mixed>  $business
     * @return array<string, mixed>
     */
    private function formatBusinessForDashboard(array $business, Business $model): array
    {
        $id = (int) $business['id'];

        $pricingLabels = [
            'per_package' => 'Paket Başı',
            'fixed' => 'Sabit Ücret',
            'monthly_fixed' => 'Aylık Sabit',
            'hourly' => 'Saatlik',
            'daily' => 'Günlük',
        ];

        return [
            'id' => $id,
            'company_name' => $business['company_name'],
            'brand_name' => $business['brand_name'],
            'logo' => $business['logo'],
            'logo_color' => $business['logo_color'],
            'logo_url' => $business['logo_url'] ?? null,
            'location' => trim($business['city'].' / '.$business['district'], ' /'),
            'pricing_model_label' => $pricingLabels[$business['pricing_model']] ?? $business['pricing_model'],
            'status' => $business['status'],
            'created_at_formatted' => $model->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'url' => route('businesses.show', $id),
        ];
    }

    /**
     * @param  array<string, mixed>  $courier
     * @return array<string, mixed>
     */
    private function formatCourierForDashboard(array $courier, Courier $model): array
    {
        $id = (int) $courier['id'];

        return [
            'id' => $id,
            'full_name' => $courier['full_name'],
            'avatar_initials' => $courier['avatar_initials'],
            'avatar_color' => $courier['avatar_color'],
            'photo_url' => $courier['photo_url'] ?? null,
            'courier_type' => $courier['courier_type'],
            'type_label' => $courier['courier_type'] === 'agency'
                ? ($courier['agency_name'] ?? CourierFormData::courierTypes()['agency'])
                : CourierFormData::courierTypes()['independent'],
            'vehicle_type_label' => $courier['vehicle_type_label'],
            'status' => $courier['status'],
            'created_at_formatted' => $model->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'url' => route('couriers.show', $id),
        ];
    }
}
