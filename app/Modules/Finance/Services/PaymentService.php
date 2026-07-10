<?php

namespace App\Modules\Finance\Services;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Data\PaymentFormData;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinancePaymentLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentPresenter $presenter,
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
            ->with(['courier', 'agency', 'earningLine', 'currentAccount', 'lines'])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FinancePayment $payment) => $this->presenter->indexRow($payment));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters)->where('is_active', true);
        $today = Carbon::today();

        $thisMonth = $items->filter(
            fn (array $row) => $row['payment_date']
                && Carbon::parse($row['payment_date'])->isSameMonth($today)
        );

        $todayPaid = $items->filter(
            fn (array $row) => $row['payment_date'] && Carbon::parse($row['payment_date'])->isSameDay($today)
        )->sum('paid_amount');

        return [
            'total_payment' => round($items->sum('total_amount'), 2),
            'this_month_payment' => round($thisMonth->sum('paid_amount'), 2),
            'pending_payment' => round($items->whereIn('status', ['pending', 'partial'])->sum('remaining_amount'), 2),
            'today_payment' => round($todayPaid, 2),
            'courier_payment' => round($items->where('recipient_type', 'courier')->sum('paid_amount'), 2),
            'agency_payment' => round($items->where('recipient_type', 'agency')->sum('paid_amount'), 2),
        ];
    }

    public function find(int $id): ?FinancePayment
    {
        return FinancePayment::query()
            ->with(['courier', 'agency', 'earningLine', 'currentAccount', 'lines'])
            ->find($id);
    }

    /**
     * @return array<string, array<int, array{id: int, name: string}>>
     */
    public function recipientsByType(): array
    {
        return [
            'courier' => $this->couriers(),
            'agency' => $this->agencies(),
            'personnel' => PaymentFormData::personnel(),
            'supplier' => PaymentFormData::suppliers(),
        ];
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
     * @return array<int, array{id: int, reference: string, recipient_type: string, recipient_id: int}>
     */
    public function earningOptions(): array
    {
        return EarningLine::query()
            ->with('courier:id,full_name')
            ->orderByDesc('id')
            ->limit(40)
            ->get(['id', 'courier_id', 'period_year'])
            ->map(fn (EarningLine $line) => [
                'id' => $line->id,
                'reference' => sprintf('HKD-%d-%04d', $line->period_year ?? now()->year, $line->id),
                'recipient_type' => 'courier',
                'recipient_id' => $line->courier_id,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): FinancePayment
    {
        return DB::transaction(function () use ($data, $user): FinancePayment {
            $recipientType = $data['recipient_type'];
            $recipientId = (int) $data['recipient_id'];
            $paymentDate = Carbon::parse($data['payment_date']);
            $totalAmount = round((float) $data['total_amount'], 2);
            $paidAmount = round((float) ($data['paid_amount'] ?? 0), 2);
            $earningLineId = ! empty($data['earning_line_id']) ? (int) $data['earning_line_id'] : null;

            if ($paidAmount > $totalAmount) {
                $paidAmount = $totalAmount;
            }

            [$courierId, $agencyId, $recipientName, $currentAccountId] = $this->resolveRecipient(
                $recipientType,
                $recipientId,
            );

            $payment = FinancePayment::query()->create([
                'recipient_type' => $recipientType,
                'courier_id' => $courierId,
                'agency_id' => $agencyId,
                'recipient_id' => $recipientId,
                'recipient_name' => $recipientName,
                'earning_line_id' => $earningLineId,
                'current_account_id' => $currentAccountId,
                'source' => $earningLineId ? 'earning' : 'manual',
                'scheduled_date' => $paymentDate->toDateString(),
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'pending',
                'is_active' => true,
                'description' => $data['description'] ?? $this->defaultDescription($recipientType, $earningLineId !== null),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $payment->update([
                'reference' => sprintf('ODM-%d-%06d', $paymentDate->year, $payment->id),
            ]);

            if ($paidAmount > 0) {
                $this->addLine($payment->fresh(), [
                    'amount' => $paidAmount,
                    'payment_date' => $paymentDate->toDateString(),
                    'payment_method' => $data['payment_method'],
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'bank_account' => $data['bank_account'] ?? null,
                ], $user);
            } else {
                $this->syncStatus($payment->fresh(['lines']));
            }

            $this->activityLog->log(
                'payment_created',
                $payment,
                description: "{$payment->reference} ödeme kaydı oluşturuldu.",
            );

            return $payment->fresh(['courier', 'agency', 'earningLine', 'currentAccount', 'lines']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $user): FinancePayment
    {
        return DB::transaction(function () use ($id, $data, $user): FinancePayment {
            $payment = $this->find($id);

            if ($payment === null) {
                abort(404);
            }

            if (! $this->canUpdate($payment)) {
                throw ValidationException::withMessages([
                    'payment' => 'Bu ödeme kaydı güncellenemez.',
                ]);
            }

            $paymentDate = Carbon::parse($data['payment_date']);
            $totalAmount = round((float) $data['total_amount'], 2);
            $paidAmount = round((float) $payment->paid_amount, 2);

            if ($totalAmount < $paidAmount) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Toplam tutar ödenen tutardan küçük olamaz.',
                ]);
            }

            $recipientType = $payment->earning_line_id
                ? $payment->recipient_type
                : $data['recipient_type'];
            $recipientId = $payment->earning_line_id
                ? (int) $payment->recipient_id
                : (int) $data['recipient_id'];

            [$courierId, $agencyId, $recipientName, $currentAccountId] = $this->resolveRecipient(
                $recipientType,
                $recipientId,
            );

            $oldValues = $payment->only([
                'recipient_type', 'recipient_id', 'scheduled_date', 'total_amount',
                'description', 'notes',
            ]);

            $payment->update([
                'recipient_type' => $recipientType,
                'courier_id' => $courierId,
                'agency_id' => $agencyId,
                'recipient_id' => $recipientId,
                'recipient_name' => $recipientName,
                'current_account_id' => $currentAccountId,
                'scheduled_date' => $paymentDate->toDateString(),
                'total_amount' => $totalAmount,
                'description' => $data['description'] ?? $payment->description,
                'notes' => $data['notes'] ?? $payment->notes,
            ]);

            $this->syncStatus($payment->fresh(['lines']));

            $this->activityLog->log(
                'payment_updated',
                $payment,
                description: "{$payment->reference} ödeme kaydı güncellendi.",
                oldValues: $oldValues,
                newValues: $payment->fresh()->only(array_keys($oldValues)),
            );

            return $payment->fresh(['courier', 'agency', 'earningLine', 'currentAccount', 'lines']);
        });
    }

    public function canUpdate(FinancePayment $payment): bool
    {
        return $payment->is_active && $payment->status !== 'paid';
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: string, 3: ?int}
     */
    private function resolveRecipient(string $type, int $id): array
    {
        if ($type === 'courier') {
            $courier = Courier::query()->findOrFail($id);
            $account = $this->currentAccounts->ensureForEntity($courier);

            return [$courier->id, null, $courier->full_name, $account->id];
        }

        if ($type === 'agency') {
            $agency = Agency::query()->findOrFail($id);
            $account = $this->currentAccounts->ensureForEntity($agency);

            return [null, $agency->id, $agency->company_name, $account->id];
        }

        $name = PaymentFormData::staticRecipientName($type, $id);

        if ($name === null) {
            throw new \InvalidArgumentException('Geçersiz alıcı seçimi.');
        }

        return [null, null, $name, null];
    }

    /**
     * @param  array<string, mixed>  $lineData
     */
    private function addLine(FinancePayment $payment, array $lineData, User $user): FinancePaymentLine
    {
        $line = FinancePaymentLine::query()->create([
            'payment_id' => $payment->id,
            'amount' => round((float) $lineData['amount'], 2),
            'payment_date' => Carbon::parse($lineData['payment_date'])->toDateString(),
            'payment_method' => $lineData['payment_method'] ?? null,
            'payment_reference' => $lineData['payment_reference'] ?? null,
            'bank_account' => $lineData['bank_account'] ?? null,
            'created_by' => $user->id,
        ]);

        $this->syncStatus($payment->fresh(['lines']));

        if ($payment->current_account_id !== null) {
            $this->currentAccounts->createMovement([
                'current_account_id' => $payment->current_account_id,
                'transaction_date' => $line->payment_date->toDateString(),
                'type' => 'payment',
                'document_no' => $line->payment_reference ?? $payment->reference,
                'amount' => (float) $line->amount,
                'description' => 'Ödeme: '.$payment->reference,
            ], $user);
        }

        return $line;
    }

    private function syncStatus(FinancePayment $payment): void
    {
        $paid = round((float) $payment->lines->sum('amount'), 2);
        $total = (float) $payment->total_amount;

        $payment->update([
            'paid_amount' => $paid,
            'status' => $this->resolveStatus($total, $paid, $payment->is_active),
        ]);
    }

    private function resolveStatus(float $total, float $paid, bool $isActive): string
    {
        if (! $isActive) {
            return 'cancelled';
        }

        $remaining = round($total - $paid, 2);

        if ($remaining <= 0) {
            return 'paid';
        }

        if ($paid > 0) {
            return 'partial';
        }

        return 'pending';
    }

    private function defaultDescription(string $type, bool $hasEarning): string
    {
        if ($hasEarning) {
            return match ($type) {
                'courier' => 'Kurye hakediş ödemesi — dönem kapanışı',
                'agency' => 'Acente hakediş ödemesi — komisyon ödemesi',
                default => 'Hakediş ödemesi',
            };
        }

        return match ($type) {
            'courier' => 'Kurye ödeme kaydı',
            'agency' => 'Acente ödeme kaydı',
            'personnel' => 'Personel maaş ve yan hak ödemesi',
            'supplier' => 'Tedarikçi fatura ödemesi',
            default => 'Manuel ödeme kaydı',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $reference = Carbon::today();

        return FinancePayment::query()
            ->when(
                ($filters['recipient_type'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('recipient_type', $filters['recipient_type'])
            )
            ->when(
                ($filters['recipient_id'] ?? 'all') !== 'all',
                function (Builder $query) use ($filters): void {
                    [$type, $id] = explode(':', $filters['recipient_id'], 2) + [null, null];

                    if ($type !== null && $id !== null) {
                        $query->where('recipient_type', $type)->where('recipient_id', (int) $id);
                    }
                }
            )
            ->when(
                ($filters['payment_status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('status', $filters['payment_status'])
            )
            ->when(
                ($filters['payment_method'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->whereHas(
                    'lines',
                    fn (Builder $lineQuery) => $lineQuery->where('payment_method', $filters['payment_method'])
                )
            )
            ->when(($filters['date_range'] ?? 'all') !== 'all', function (Builder $query) use ($filters, $reference): void {
                $range = $filters['date_range'];

                $query->where(function (Builder $dateQuery) use ($range, $reference): void {
                    $dateQuery->whereHas('lines', function (Builder $lineQuery) use ($range, $reference): void {
                        if ($range === 'today') {
                            $lineQuery->whereDate('payment_date', $reference);

                            return;
                        }

                        if ($range === 'week') {
                            $lineQuery->whereBetween('payment_date', [
                                $reference->copy()->startOfWeek()->toDateString(),
                                $reference->copy()->endOfWeek()->toDateString(),
                            ]);

                            return;
                        }

                        if ($range === 'month') {
                            $lineQuery->whereYear('payment_date', $reference->year)
                                ->whereMonth('payment_date', $reference->month);

                            return;
                        }

                        if ($range === 'year') {
                            $lineQuery->whereYear('payment_date', $reference->year);
                        }
                    })->orWhere(function (Builder $scheduledQuery) use ($range, $reference): void {
                        if ($range === 'today') {
                            $scheduledQuery->whereDate('scheduled_date', $reference);

                            return;
                        }

                        if ($range === 'week') {
                            $scheduledQuery->whereBetween('scheduled_date', [
                                $reference->copy()->startOfWeek()->toDateString(),
                                $reference->copy()->endOfWeek()->toDateString(),
                            ]);

                            return;
                        }

                        if ($range === 'month') {
                            $scheduledQuery->whereYear('scheduled_date', $reference->year)
                                ->whereMonth('scheduled_date', $reference->month);

                            return;
                        }

                        if ($range === 'year') {
                            $scheduledQuery->whereYear('scheduled_date', $reference->year);
                        }
                    });
                });
            });
    }
}
