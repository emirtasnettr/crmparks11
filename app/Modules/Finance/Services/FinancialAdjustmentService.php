<?php

namespace App\Modules\Finance\Services;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinancialAdjustment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FinancialAdjustmentService
{
    public function __construct(
        private readonly CurrentAccountService $currentAccounts,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array{direction: string, amount: float|int|string, reason: string, earning_line_id?: int|null}  $data
     */
    public function adjustCourier(Courier $courier, array $data, User $actor): FinancialAdjustment
    {
        return $this->adjust('courier', $courier, $data, $actor);
    }

    /**
     * @param  array{direction: string, amount: float|int|string, reason: string, earning_line_id?: int|null}  $data
     */
    public function adjustBusiness(Business $business, array $data, User $actor): FinancialAdjustment
    {
        return $this->adjust('business', $business, $data, $actor);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEarningLine(int $earningLineId): array
    {
        if (! Schema::hasTable('financial_adjustments')) {
            return [];
        }

        return $this->presentList(
            FinancialAdjustment::query()
                ->with('creator:id,name')
                ->where('earning_line_id', $earningLineId)
                ->orderByDesc('id')
                ->limit(50)
                ->get()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForTarget(string $targetType, int $targetId): array
    {
        if (! Schema::hasTable('financial_adjustments')) {
            return [];
        }

        return $this->presentList(
            FinancialAdjustment::query()
                ->with('creator:id,name')
                ->where('target_type', $targetType)
                ->where('target_id', $targetId)
                ->orderByDesc('id')
                ->limit(50)
                ->get()
        );
    }

    /**
     * @param  array{direction: string, amount: float|int|string, reason: string, earning_line_id?: int|null}  $data
     */
    private function adjust(string $targetType, Model $entity, array $data, User $actor): FinancialAdjustment
    {
        if (! $actor->hasRole('super_admin')) {
            abort(403, 'Bu işlem yalnızca süper admin tarafından yapılabilir.');
        }

        $direction = (string) $data['direction'];
        $amount = round((float) $data['amount'], 2);
        $reason = trim((string) $data['reason']);

        if (! in_array($direction, ['credit', 'debit'], true)) {
            throw ValidationException::withMessages(['direction' => 'Geçersiz işlem yönü.']);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Tutar 0’dan büyük olmalıdır.']);
        }

        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['reason' => 'Neden en az 5 karakter olmalıdır.']);
        }

        $earningLineId = isset($data['earning_line_id']) && $data['earning_line_id'] !== ''
            ? (int) $data['earning_line_id']
            : null;

        $earningLine = null;
        if ($earningLineId !== null) {
            $earningLine = EarningLine::query()->findOrFail($earningLineId);
            $this->assertEarningLineBelongsToTarget($earningLine, $targetType, (int) $entity->getKey());
        }

        return DB::transaction(function () use ($targetType, $entity, $direction, $amount, $reason, $actor, $earningLine): FinancialAdjustment {
            $account = $this->currentAccounts->ensureForEntity($entity);

            $adjustment = FinancialAdjustment::query()->create([
                'target_type' => $targetType,
                'target_id' => (int) $entity->getKey(),
                'current_account_id' => $account->id,
                'earning_line_id' => $earningLine?->id,
                'direction' => $direction,
                'amount' => $amount,
                'reason' => $reason,
                'created_by' => $actor->id,
            ]);

            $movement = $this->currentAccounts->createMovement([
                'current_account_id' => $account->id,
                'transaction_date' => now()->toDateString(),
                'type' => $direction === 'credit' ? 'credit_note' : 'debit_note',
                'amount' => $amount,
                'description' => $reason,
                'related_type' => 'financial_adjustment',
                'related_id' => $adjustment->id,
            ], $actor);

            $adjustment->update([
                'current_account_movement_id' => $movement->id,
            ]);

            if ($earningLine !== null && $this->earningLineIsEditable($earningLine)) {
                $this->applyToEarningLine($earningLine, $direction, $amount);
            }

            $targetLabel = $targetType === 'courier' ? 'kurye' : 'işletme';
            $actionLabel = $direction === 'credit' ? 'ekleme' : 'düşürme';

            $this->activityLog->log(
                'financial_adjustment_created',
                $adjustment,
                description: "Süper admin {$targetLabel} tutar {$actionLabel}: {$amount} TL — {$reason}",
                newValues: [
                    'target_type' => $targetType,
                    'target_id' => (int) $entity->getKey(),
                    'direction' => $direction,
                    'amount' => $amount,
                    'reason' => $reason,
                    'earning_line_id' => $earningLine?->id,
                    'current_account_id' => $account->id,
                    'current_account_movement_id' => $movement->id,
                ],
            );

            return $adjustment->fresh(['creator', 'movement']);
        });
    }

    private function assertEarningLineBelongsToTarget(EarningLine $line, string $targetType, int $targetId): void
    {
        if ($targetType === 'courier' && (int) $line->courier_id !== $targetId) {
            throw ValidationException::withMessages([
                'earning_line_id' => 'Hakediş bu kuryeye ait değil.',
            ]);
        }

        if ($targetType === 'business' && (int) $line->business_id !== $targetId) {
            throw ValidationException::withMessages([
                'earning_line_id' => 'Hakediş bu işletmeye ait değil.',
            ]);
        }
    }

    private function earningLineIsEditable(EarningLine $line): bool
    {
        $line->loadMissing('status');
        $code = $line->status?->code
            ?? EarningStatus::query()->whereKey($line->status_id)->value('code');

        return ! in_array((string) $code, ['paid', 'cancelled'], true);
    }

    private function applyToEarningLine(EarningLine $line, string $direction, float $amount): void
    {
        $extraPayment = (float) $line->extra_payment;
        $deduction = (float) $line->deduction;
        $courierTotal = (float) $line->courier_total;
        $revenueTotal = (float) $line->revenue_total;
        $extraExpense = (float) $line->extra_expense;

        if ($direction === 'credit') {
            $extraPayment = round($extraPayment + $amount, 2);
        } else {
            $deduction = round($deduction + $amount, 2);
        }

        $net = round($courierTotal + $extraPayment - $deduction, 2);
        $profit = round($revenueTotal - $courierTotal - $extraExpense + $extraPayment - $deduction, 2);

        $line->update([
            'extra_payment' => $extraPayment,
            'deduction' => $deduction,
            'net_courier_payment' => $net,
            'profit' => $profit,
        ]);
    }

    /**
     * @param  Collection<int, FinancialAdjustment>  $rows
     * @return list<array<string, mixed>>
     */
    private function presentList(Collection $rows): array
    {
        return $rows->map(fn (FinancialAdjustment $row) => [
            'id' => $row->id,
            'direction' => $row->direction,
            'direction_label' => $row->directionLabel(),
            'amount' => (float) $row->amount,
            'amount_signed' => $row->isCredit() ? (float) $row->amount : -1 * (float) $row->amount,
            'reason' => $row->reason,
            'created_by_name' => $row->creator?->name ?? '—',
            'created_at' => $row->created_at?->format('d.m.Y H:i') ?? '—',
        ])->values()->all();
    }
}
