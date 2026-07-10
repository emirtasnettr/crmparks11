<?php

namespace App\Modules\Report\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Report\Data\ReportCatalog;
use App\Support\EarningStatusMapper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog($user): array
    {
        return ReportCatalog::forUser($user);
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function earningsSummary(array $filters): array
    {
        $year = (int) ($filters['year'] ?: now()->year);
        $month = ($filters['month'] ?? 'all') === 'all' ? null : (int) $filters['month'];

        $lines = EarningLine::query()
            ->with(['business', 'courier', 'status'])
            ->where('period_year', $year)
            ->when($month !== null, fn ($q) => $q->where('period_month', $month))
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->get();

        $rows = $lines->map(function (EarningLine $line): array {
            $status = EarningStatusMapper::toUiCode($line->status?->code ?? 'draft');

            return [
                'id' => $line->id,
                'business' => $line->business?->displayName() ?? '—',
                'courier' => $line->courier?->full_name ?? '—',
                'period' => sprintf('%02d/%d', $line->period_month, $line->period_year),
                'status' => $status,
                'revenue' => (float) $line->revenue_total,
                'expense' => round((float) $line->net_courier_payment + (float) $line->agency_payment + (float) $line->extra_expense, 2),
                'profit' => (float) $line->profit,
                'revenue_formatted' => MoneyCalculator::format((float) $line->revenue_total),
                'expense_formatted' => MoneyCalculator::format((float) $line->net_courier_payment + (float) $line->agency_payment + (float) $line->extra_expense),
                'profit_formatted' => MoneyCalculator::format((float) $line->profit),
                'url' => route('businesses.earnings.show', $line->id),
            ];
        });

        return [
            'filters' => [
                'year' => $year,
                'month' => $filters['month'] ?? 'all',
            ],
            'summary' => [
                'count' => $rows->count(),
                'revenue' => round($rows->sum('revenue'), 2),
                'expense' => round($rows->sum('expense'), 2),
                'profit' => round($rows->sum('profit'), 2),
                'revenue_formatted' => MoneyCalculator::format((float) $rows->sum('revenue')),
                'expense_formatted' => MoneyCalculator::format((float) $rows->sum('expense')),
                'profit_formatted' => MoneyCalculator::format((float) $rows->sum('profit')),
            ],
            'rows' => $rows->values()->all(),
        ];
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function earningsExportRows(array $filters): array
    {
        $data = $this->earningsSummary($filters);

        return [
            'headings' => ['İşletme', 'Kurye', 'Dönem', 'Durum', 'Gelir', 'Gider', 'Kâr'],
            'rows' => collect($data['rows'])->map(fn (array $row) => [
                $row['business'],
                $row['courier'],
                $row['period'],
                $row['status'],
                $row['revenue'],
                $row['expense'],
                $row['profit'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function collectionsAging(): array
    {
        $today = Carbon::today();

        $collections = FinanceCollection::query()
            ->with('business')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'current' => ['label' => 'Vadesi Gelmemiş', 'count' => 0, 'amount' => 0.0],
            '0_30' => ['label' => '0–30 Gün Gecikmiş', 'count' => 0, 'amount' => 0.0],
            '31_60' => ['label' => '31–60 Gün Gecikmiş', 'count' => 0, 'amount' => 0.0],
            '61_plus' => ['label' => '61+ Gün Gecikmiş', 'count' => 0, 'amount' => 0.0],
        ];

        $rows = $collections->map(function (FinanceCollection $collection) use ($today, &$buckets): array {
            $remaining = round((float) $collection->total_amount - (float) $collection->collected_amount, 2);
            $days = (int) $collection->due_date->diffInDays($today, false);

            $bucket = match (true) {
                $days < 0 => 'current',
                $days <= 30 => '0_30',
                $days <= 60 => '31_60',
                default => '61_plus',
            };

            $buckets[$bucket]['count']++;
            $buckets[$bucket]['amount'] += $remaining;

            return [
                'id' => $collection->id,
                'business' => $collection->business?->displayName() ?? '—',
                'reference' => $collection->reference,
                'due_date_formatted' => $collection->due_date->format('d.m.Y'),
                'days_overdue' => max(0, $days),
                'bucket' => $bucket,
                'bucket_label' => $buckets[$bucket]['label'],
                'amount' => $remaining,
                'amount_formatted' => MoneyCalculator::format($remaining),
                'url' => route('finance.collections.show', $collection->id),
            ];
        });

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['amount_formatted'] = MoneyCalculator::format($bucket['amount']);
        }

        return [
            'buckets' => $buckets,
            'summary' => [
                'count' => $rows->count(),
                'amount' => round($rows->sum('amount'), 2),
                'amount_formatted' => MoneyCalculator::format((float) $rows->sum('amount')),
            ],
            'rows' => $rows->values()->all(),
        ];
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function collectionsExportRows(): array
    {
        $data = $this->collectionsAging();

        return [
            'headings' => ['İşletme', 'Referans', 'Vade', 'Gecikme (gün)', 'Grup', 'Kalan Tutar'],
            'rows' => collect($data['rows'])->map(fn (array $row) => [
                $row['business'],
                $row['reference'],
                $row['due_date_formatted'],
                $row['days_overdue'],
                $row['bucket_label'],
                $row['amount'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function operationsSummary(): array
    {
        $activeAssignments = BusinessCourierAssignment::query()
            ->where(function ($q): void {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->count();

        return [
            'stats' => [
                'businesses' => Business::query()->count(),
                'active_businesses' => Business::query()->where('status', 'active')->count(),
                'couriers' => Courier::query()->count(),
                'active_couriers' => Courier::query()->where('status', 'active')->count(),
                'agencies' => Agency::query()->count(),
                'active_agencies' => Agency::query()->where('status', 'active')->count(),
                'active_assignments' => $activeAssignments,
                'earnings_this_month' => EarningLine::query()
                    ->where('period_year', now()->year)
                    ->where('period_month', now()->month)
                    ->count(),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function courierPerformanceSummary(array $filters): array
    {
        $year = (int) ($filters['year'] ?: now()->year);
        $month = ($filters['month'] ?? 'all') === 'all' ? null : (int) $filters['month'];

        $lines = EarningLine::query()
            ->with(['courier', 'status'])
            ->where('period_year', $year)
            ->when($month !== null, fn ($q) => $q->where('period_month', $month))
            ->get();

        $rows = $lines
            ->groupBy('courier_id')
            ->map(function (Collection $group) use ($year, $month): array {
                /** @var EarningLine $first */
                $first = $group->first();
                $courier = $first->courier;
                $revenue = round($group->sum(fn (EarningLine $line) => (float) $line->revenue_total), 2);
                $courierPay = round($group->sum(fn (EarningLine $line) => (float) $line->net_courier_payment), 2);
                $profit = round($group->sum(fn (EarningLine $line) => (float) $line->profit), 2);
                $packages = (int) $group->sum('package_count');

                return [
                    'courier_id' => $first->courier_id,
                    'courier' => $courier?->full_name ?? '—',
                    'packages' => $packages,
                    'lines' => $group->count(),
                    'revenue' => $revenue,
                    'courier_payment' => $courierPay,
                    'profit' => $profit,
                    'revenue_formatted' => MoneyCalculator::format($revenue),
                    'courier_payment_formatted' => MoneyCalculator::format($courierPay),
                    'profit_formatted' => MoneyCalculator::format($profit),
                    'url' => route('couriers.earnings.index', [
                        'courier_id' => $first->courier_id,
                        'period_year' => $year,
                        'period_month' => $month ?? 'all',
                    ]),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return [
            'filters' => [
                'year' => $year,
                'month' => $filters['month'] ?? 'all',
            ],
            'summary' => [
                'count' => $rows->count(),
                'packages' => (int) $rows->sum('packages'),
                'revenue' => round($rows->sum('revenue'), 2),
                'courier_payment' => round($rows->sum('courier_payment'), 2),
                'profit' => round($rows->sum('profit'), 2),
                'revenue_formatted' => MoneyCalculator::format((float) $rows->sum('revenue')),
                'courier_payment_formatted' => MoneyCalculator::format((float) $rows->sum('courier_payment')),
                'profit_formatted' => MoneyCalculator::format((float) $rows->sum('profit')),
            ],
            'rows' => $rows->all(),
        ];
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function courierPerformanceExportRows(array $filters): array
    {
        $data = $this->courierPerformanceSummary($filters);

        return [
            'headings' => ['Kurye', 'Paket', 'Kayıt', 'Gelir', 'Kurye Ödemesi', 'Kâr'],
            'rows' => collect($data['rows'])->map(fn (array $row) => [
                $row['courier'],
                $row['packages'],
                $row['lines'],
                $row['revenue'],
                $row['courier_payment'],
                $row['profit'],
            ])->all(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function agencyShareSummary(array $filters): array
    {
        $year = (int) ($filters['year'] ?: now()->year);
        $month = ($filters['month'] ?? 'all') === 'all' ? null : (int) $filters['month'];

        $lines = EarningLine::query()
            ->with(['courier.agency', 'status'])
            ->where('period_year', $year)
            ->when($month !== null, fn ($q) => $q->where('period_month', $month))
            ->whereHas('courier', fn ($q) => $q->whereNotNull('agency_id'))
            ->get();

        $rows = $lines
            ->groupBy(fn (EarningLine $line) => $line->courier?->agency_id)
            ->filter(fn ($group, $agencyId) => $agencyId !== null)
            ->map(function (Collection $group) use ($year, $month): array {
                /** @var EarningLine $first */
                $first = $group->first();
                $agency = $first->courier?->agency;
                $agencyPayment = round($group->sum(fn (EarningLine $line) => (float) $line->agency_payment), 2);
                $revenue = round($group->sum(fn (EarningLine $line) => (float) $line->revenue_total), 2);
                $packages = (int) $group->sum('package_count');
                $agencyId = $first->courier?->agency_id;

                return [
                    'agency_id' => $agencyId,
                    'agency' => $agency?->displayName() ?? '—',
                    'couriers' => $group->pluck('courier_id')->unique()->count(),
                    'packages' => $packages,
                    'lines' => $group->count(),
                    'revenue' => $revenue,
                    'agency_payment' => $agencyPayment,
                    'revenue_formatted' => MoneyCalculator::format($revenue),
                    'agency_payment_formatted' => MoneyCalculator::format($agencyPayment),
                    'url' => route('agencies.earnings.index', [
                        'agency_id' => $agencyId,
                        'period_year' => $year,
                        'period_month' => $month ?? 'all',
                    ]),
                ];
            })
            ->sortByDesc('agency_payment')
            ->values();

        return [
            'filters' => [
                'year' => $year,
                'month' => $filters['month'] ?? 'all',
            ],
            'summary' => [
                'count' => $rows->count(),
                'packages' => (int) $rows->sum('packages'),
                'revenue' => round($rows->sum('revenue'), 2),
                'agency_payment' => round($rows->sum('agency_payment'), 2),
                'revenue_formatted' => MoneyCalculator::format((float) $rows->sum('revenue')),
                'agency_payment_formatted' => MoneyCalculator::format((float) $rows->sum('agency_payment')),
            ],
            'rows' => $rows->all(),
        ];
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function agencyShareExportRows(array $filters): array
    {
        $data = $this->agencyShareSummary($filters);

        return [
            'headings' => ['Acente', 'Kurye', 'Paket', 'Kayıt', 'Gelir', 'Acente Payı'],
            'rows' => collect($data['rows'])->map(fn (array $row) => [
                $row['agency'],
                $row['couriers'],
                $row['packages'],
                $row['lines'],
                $row['revenue'],
                $row['agency_payment'],
            ])->all(),
        ];
    }
}
