<?php

namespace App\Modules\Finance\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Data\CurrentAccountFormData;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinancePayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurrentAccountPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(CurrentAccount $account): array
    {
        return $this->enrich($account, includeAllMovements: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailRow(CurrentAccount $account): array
    {
        return $this->enrich($account, includeAllMovements: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(CurrentAccount $account, bool $includeAllMovements): array
    {
        $account->loadMissing(['movements', 'accountable']);
        $movements = $this->enrichMovements($account->movements);
        $totals = $this->calculateTotals($movements);
        $balance = round($totals['debit'] - $totals['credit'], 2);
        $balanceStatus = $this->resolveBalanceStatus($balance);
        $lastMovement = collect($movements)->sortByDesc('date')->first();
        $lastInvoice = collect($movements)->first(fn (array $movement) => in_array($movement['type'], ['invoice', 'debit_note'], true));
        $lastEarning = collect($movements)->first(fn (array $movement) => $movement['type'] === 'earning');
        $referenceDate = Carbon::today();

        $overdueReceivable = 0.0;
        $overduePayable = 0.0;

        if ($account->account_type === 'business' && $balance > 0) {
            $overdueReceivable = $this->overdueReceivableForAccount($account, $balance);
        }

        if (in_array($account->account_type, ['courier', 'agency'], true) && $balance < 0) {
            $overduePayable = $this->overduePayableForAccount($account, abs($balance));
        }

        $brandName = null;
        if ($account->account_type === 'business' && $account->accountable instanceof Business) {
            $brand = trim((string) ($account->accountable->brand_name ?? ''));
            $brandName = $brand !== '' ? $brand : null;
        }

        return [
            'id' => $account->id,
            'code' => $account->code,
            'type' => $account->account_type,
            'entity_type' => $account->account_type,
            'entity_id' => $account->accountable_id,
            'brand_name' => $brandName,
            'title' => $account->title,
            'phone' => $account->phone ?? '—',
            'email' => $account->email,
            'city' => $account->city ?? '—',
            'tax_number' => $account->tax_number,
            'status' => $account->status,
            'can_update' => true,
            'can_deactivate' => $account->status === 'active',
            'address' => $account->address,
            'type_label' => CurrentAccountFormData::accountTypes()[$account->account_type] ?? '—',
            'status_label' => CurrentAccountFormData::statuses()[$account->status] ?? '—',
            'total_debit' => $totals['debit'],
            'total_credit' => $totals['credit'],
            'total_debit_formatted' => money_excl_vat($totals['debit']),
            'total_credit_formatted' => money_excl_vat($totals['credit']),
            'balance' => $balance,
            'balance_formatted' => money_excl_vat($balance),
            'balance_status' => $balanceStatus,
            'balance_status_label' => CurrentAccountFormData::balanceStatuses()[$balanceStatus],
            'balance_tone' => match ($balanceStatus) {
                'receivable' => 'positive',
                'payable' => 'negative',
                default => 'zero',
            },
            'last_movement_at' => $lastMovement['date'] ?? null,
            'last_movement_formatted' => $lastMovement ? Carbon::parse($lastMovement['date'])->format('d.m.Y') : '—',
            'last_movement_label' => $lastMovement['type_label'] ?? '—',
            'overdue_receivable' => $overdueReceivable,
            'overdue_payable' => $overduePayable,
            'overdue_receivable_formatted' => money_excl_vat($overdueReceivable),
            'overdue_payable_formatted' => money_excl_vat($overduePayable),
            'last_invoice' => $lastInvoice ? [
                'document_no' => $lastInvoice['document_no'],
                'date' => Carbon::parse($lastInvoice['date'])->format('d.m.Y'),
                'amount' => $lastInvoice['debit'],
                'amount_formatted' => money_excl_vat($lastInvoice['debit']),
            ] : null,
            'last_earning' => $lastEarning ? [
                'document_no' => $lastEarning['document_no'],
                'date' => Carbon::parse($lastEarning['date'])->format('d.m.Y'),
                'amount' => $lastEarning['credit'],
                'amount_formatted' => money_excl_vat($lastEarning['credit']),
            ] : null,
            'movements' => $includeAllMovements ? $movements : [],
            'recent_movements' => array_slice($movements, 0, 5),
            'days_since_last_movement' => $lastMovement
                ? (int) Carbon::parse($lastMovement['date'])->diffInDays($referenceDate)
                : null,
        ];
    }

    /**
     * @param  Collection<int, CurrentAccountMovement>  $movements
     * @return array<int, array<string, mixed>>
     */
    private function enrichMovements(Collection $movements): array
    {
        $sorted = $movements->sortBy('transaction_date')->values();
        $running = 0.0;

        return $sorted
            ->map(function (CurrentAccountMovement $movement) use (&$running) {
                $debit = (float) $movement->debit;
                $credit = (float) $movement->credit;
                $running = round($running + $debit - $credit, 2);

                return [
                    'id' => $movement->id,
                    'date' => $movement->transaction_date->toDateString(),
                    'date_formatted' => $movement->transaction_date->format('d.m.Y'),
                    'document_no' => $movement->document_no ?? '—',
                    'type' => $movement->type,
                    'type_label' => CurrentAccountFormData::movementTypeLabels()[$movement->type] ?? $movement->type,
                    'debit' => $debit,
                    'credit' => $credit,
                    'debit_formatted' => $debit > 0 ? money_excl_vat($debit) : '—',
                    'credit_formatted' => $credit > 0 ? money_excl_vat($credit) : '—',
                    'balance' => $running,
                    'balance_formatted' => money_excl_vat($running),
                    'description' => $movement->description,
                    'related_type' => $movement->related_type,
                    'related_id' => $movement->related_id,
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $movements
     * @return array{debit: float, credit: float}
     */
    private function calculateTotals(array $movements): array
    {
        return [
            'debit' => round(collect($movements)->sum('debit'), 2),
            'credit' => round(collect($movements)->sum('credit'), 2),
        ];
    }

    private function resolveBalanceStatus(float $balance): string
    {
        if ($balance > 0) {
            return 'receivable';
        }

        if ($balance < 0) {
            return 'payable';
        }

        return 'zero';
    }

    /**
     * Vadesi geçmiş açık tahsilat bakiyesi (cari bakiyesi ile sınırlı).
     */
    private function overdueReceivableForAccount(CurrentAccount $account, float $balance): float
    {
        $today = Carbon::today()->toDateString();

        $overdue = (float) FinanceCollection::query()
            ->where('current_account_id', $account->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereDate('due_date', '<', $today)
            ->get()
            ->sum(fn (FinanceCollection $row) => max(
                0,
                round((float) $row->total_amount - (float) $row->collected_amount, 2),
            ));

        return round(min($balance, max(0, $overdue)), 2);
    }

    /**
     * Vadesi geçmiş açık hakediş/ödeme bakiyesi (cari bakiyesi ile sınırlı).
     */
    private function overduePayableForAccount(CurrentAccount $account, float $absolutePayable): float
    {
        $today = Carbon::today()->toDateString();

        $overdue = (float) FinancePayment::query()
            ->where('current_account_id', $account->id)
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('scheduled_date', '<', $today)
            ->get()
            ->sum(fn (FinancePayment $row) => max(
                0,
                round((float) $row->total_amount - (float) $row->paid_amount, 2),
            ));

        return round(min($absolutePayable, max(0, $overdue)), 2);
    }
}
