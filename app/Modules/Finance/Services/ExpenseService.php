<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceExpense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        private readonly ExpensePresenter $presenter,
        private readonly CurrentAccountService $currentAccounts,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['courier', 'agency', 'currentAccount', 'earningLine'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FinanceExpense $expense) => $this->presenter->indexRow($expense));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters);
        $today = Carbon::today();

        $thisMonth = $items->filter(
            fn (array $row) => Carbon::parse($row['expense_date'])->isSameMonth($today)
        );

        return [
            'total_expense' => round($items->sum('amount'), 2),
            'this_month_expense' => round($thisMonth->sum('amount'), 2),
            'paid_amount' => round($items->where('payment_status', 'paid')->sum('amount'), 2),
            'pending_payment' => round($items->whereIn('payment_status', ['pending', 'overdue'])->sum('amount'), 2),
            'courier_expense' => round($items->where('expense_type', 'courier_earning')->sum('amount'), 2),
            'agency_expense' => round($items->where('expense_type', 'agency_earning')->sum('amount'), 2),
        ];
    }

    public function find(int $id): ?FinanceExpense
    {
        return FinanceExpense::query()
            ->with(['courier', 'agency', 'currentAccount', 'earningLine'])
            ->find($id);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->company_name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): FinanceExpense
    {
        return DB::transaction(function () use ($data, $user): FinanceExpense {
            $expenseType = $data['expense_type'];
            $courierId = ! empty($data['courier_id']) ? (int) $data['courier_id'] : null;
            $agencyId = ! empty($data['agency_id']) ? (int) $data['agency_id'] : null;
            $paymentStatus = $data['payment_status'] ?? 'pending';
            $expenseDate = Carbon::parse($data['expense_date'] ?? now()->toDateString());
            $isEarningType = in_array($expenseType, ['courier_earning', 'agency_earning'], true);
            $currentAccountId = $this->resolveCurrentAccountId($courierId, $agencyId);

            $expense = FinanceExpense::query()->create([
                'expense_type' => $expenseType,
                'source' => $isEarningType ? 'earning' : 'manual',
                'courier_id' => $courierId,
                'agency_id' => $agencyId,
                'earning_line_id' => $data['earning_line_id'] ?? null,
                'current_account_id' => $currentAccountId,
                'amount' => round((float) $data['amount'], 2),
                'vat_rate' => (int) ($data['vat_rate'] ?? 20),
                'expense_date' => $expenseDate->toDateString(),
                'payment_status' => $paymentStatus,
                'payment_date' => $paymentStatus === 'paid'
                    ? ($data['payment_date'] ?? $expenseDate->toDateString())
                    : null,
                'document_no' => $data['document_no'] ?? null,
                'description' => $data['description'] ?? $this->defaultDescription($expenseType),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $expense->update([
                'reference' => sprintf('GDR-%d-%06d', $expenseDate->year, $expense->id),
            ]);

            $this->recordCurrentAccountMovement($expense->fresh(), $user);

            $this->activityLog->log(
                'expense_created',
                $expense,
                description: "{$expense->reference} gider kaydı oluşturuldu.",
            );

            return $expense->fresh(['courier', 'agency', 'currentAccount', 'earningLine']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $user): FinanceExpense
    {
        return DB::transaction(function () use ($id, $data, $user): FinanceExpense {
            $expense = $this->find($id);

            if ($expense === null) {
                abort(404);
            }

            if (! $this->canUpdate($expense)) {
                throw ValidationException::withMessages([
                    'expense' => 'Bu gider kaydı güncellenemez.',
                ]);
            }

            $expenseType = $data['expense_type'];
            $courierId = ! empty($data['courier_id']) ? (int) $data['courier_id'] : null;
            $agencyId = ! empty($data['agency_id']) ? (int) $data['agency_id'] : null;
            $paymentStatus = $data['payment_status'] ?? $expense->payment_status;
            $expenseDate = Carbon::parse($data['expense_date'] ?? $expense->expense_date->toDateString());
            $currentAccountId = $this->resolveCurrentAccountId($courierId, $agencyId);

            $oldValues = $expense->only([
                'expense_type', 'courier_id', 'agency_id', 'amount', 'vat_rate',
                'expense_date', 'payment_status', 'document_no', 'description', 'notes',
            ]);

            $expense->update([
                'expense_type' => $expenseType,
                'courier_id' => $courierId,
                'agency_id' => $agencyId,
                'current_account_id' => $currentAccountId,
                'amount' => round((float) $data['amount'], 2),
                'vat_rate' => (int) ($data['vat_rate'] ?? 20),
                'expense_date' => $expenseDate->toDateString(),
                'payment_status' => $paymentStatus,
                'payment_date' => $paymentStatus === 'paid'
                    ? ($data['payment_date'] ?? $expense->payment_date?->toDateString() ?? $expenseDate->toDateString())
                    : null,
                'document_no' => $data['document_no'] ?? $expense->document_no,
                'description' => $data['description'] ?? $expense->description,
                'notes' => $data['notes'] ?? $expense->notes,
            ]);

            $this->activityLog->log(
                'expense_updated',
                $expense,
                description: "{$expense->reference} gider kaydı güncellendi.",
                oldValues: $oldValues,
                newValues: $expense->fresh()->only(array_keys($oldValues)),
            );

            return $expense->fresh(['courier', 'agency', 'currentAccount', 'earningLine']);
        });
    }

    public function canUpdate(FinanceExpense $expense): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $reference = Carbon::today();

        return FinanceExpense::query()
            ->when(
                ($filters['expense_type'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('expense_type', $filters['expense_type'])
            )
            ->when(
                ($filters['courier_id'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('courier_id', (int) $filters['courier_id'])
            )
            ->when(
                ($filters['agency_id'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('agency_id', (int) $filters['agency_id'])
            )
            ->when(
                ($filters['payment_status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('payment_status', $filters['payment_status'])
            )
            ->when(($filters['date_range'] ?? 'all') !== 'all', function (Builder $query) use ($filters, $reference): void {
                $range = $filters['date_range'];

                if ($range === 'today') {
                    $query->whereDate('expense_date', $reference);

                    return;
                }

                if ($range === 'week') {
                    $query->whereBetween('expense_date', [
                        $reference->copy()->startOfWeek()->toDateString(),
                        $reference->copy()->endOfWeek()->toDateString(),
                    ]);

                    return;
                }

                if ($range === 'month') {
                    $query->whereYear('expense_date', $reference->year)
                        ->whereMonth('expense_date', $reference->month);

                    return;
                }

                if ($range === 'year') {
                    $query->whereYear('expense_date', $reference->year);
                }
            });
    }

    private function resolveCurrentAccountId(?int $courierId, ?int $agencyId): ?int
    {
        if ($courierId !== null) {
            $courier = Courier::query()->findOrFail($courierId);

            return $this->currentAccounts->ensureForEntity($courier)->id;
        }

        if ($agencyId !== null) {
            $agency = Agency::query()->findOrFail($agencyId);

            return $this->currentAccounts->ensureForEntity($agency)->id;
        }

        return null;
    }

    private function recordCurrentAccountMovement(FinanceExpense $expense, User $user): void
    {
        if ($expense->current_account_id === null) {
            return;
        }

        $movementType = $expense->payment_status === 'paid' ? 'payment' : 'debit_note';

        $this->currentAccounts->createMovement([
            'current_account_id' => $expense->current_account_id,
            'transaction_date' => $expense->expense_date->toDateString(),
            'type' => $movementType,
            'document_no' => $expense->document_no ?? $expense->reference,
            'amount' => (float) $expense->amount,
            'description' => 'Gider kaydı: '.$expense->reference,
        ], $user);
    }

    private function defaultDescription(string $type): string
    {
        return match ($type) {
            'courier_earning' => 'Kurye hakediş ödemesi — dönem kapanışı',
            'agency_earning' => 'Acente komisyon hakediş ödemesi',
            'personnel' => 'Personel maaş ve yan hak ödemesi',
            'fuel' => 'Araç yakıt gideri — filo operasyonu',
            'office' => 'Ofis kırtasiye ve genel gider',
            'software' => 'Yazılım lisans ve abonelik gideri',
            'advertising' => 'Dijital reklam ve pazarlama gideri',
            'tax' => 'KDV ve stopaj ödemesi',
            'rent' => 'Ofis kira ödemesi',
            default => 'Genel operasyon gideri',
        };
    }
}
