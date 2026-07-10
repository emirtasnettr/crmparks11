<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Modules\Finance\Data\ExpenseFormData;
use App\Modules\Finance\Models\FinanceExpense;

class ExpensePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(FinanceExpense $expense): array
    {
        return $this->enrich($expense);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(FinanceExpense $expense): array
    {
        return $this->enrich($expense, detailed: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(FinanceExpense $expense, bool $detailed = false): array
    {
        $expense->loadMissing(['courier', 'agency', 'currentAccount', 'earningLine']);

        $amount = (float) $expense->amount;
        $vatAmount = round($amount * ($expense->vat_rate / 100), 2);
        $grossAmount = round($amount + $vatAmount, 2);
        $payeeDisplay = $expense->courier?->full_name ?? $expense->agency?->displayName() ?? '—';

        $row = [
            'id' => $expense->id,
            'reference' => $expense->reference,
            'expense_type' => $expense->expense_type,
            'source' => $expense->source,
            'courier_id' => $expense->courier_id,
            'agency_id' => $expense->agency_id,
            'earning_id' => $expense->earning_line_id,
            'earning_reference' => $this->earningReference($expense),
            'current_account_id' => $expense->current_account_id,
            'current_account_code' => $expense->currentAccount?->code,
            'amount' => $amount,
            'vat_rate' => (int) $expense->vat_rate,
            'expense_date' => $expense->expense_date->toDateString(),
            'created_at' => $expense->created_at?->toDateString(),
            'payment_status' => $expense->payment_status,
            'payment_date' => $expense->payment_date?->toDateString(),
            'document_no' => $expense->document_no,
            'description' => $expense->description,
            'notes' => $expense->notes,
            'can_update' => true,
            'expense_type_label' => ExpenseFormData::expenseTypes()[$expense->expense_type] ?? $expense->expense_type,
            'payment_status_label' => ExpenseFormData::paymentStatuses()[$expense->payment_status] ?? $expense->payment_status,
            'source_label' => ExpenseFormData::sources()[$expense->source] ?? $expense->source,
            'courier_name' => $expense->courier?->full_name,
            'agency_name' => $expense->agency?->company_name,
            'payee_display' => $payeeDisplay,
            'amount_formatted' => money_excl_vat($amount),
            'vat_amount' => $vatAmount,
            'vat_amount_formatted' => MoneyCalculator::formatVatAmount($vatAmount),
            'gross_amount' => $grossAmount,
            'gross_amount_formatted' => MoneyCalculator::formatIncludingVat($grossAmount),
            'expense_date_formatted' => $expense->expense_date->format('d.m.Y'),
            'created_at_formatted' => $expense->created_at?->format('d.m.Y') ?? '—',
            'payment_date_formatted' => $expense->payment_date?->format('d.m.Y') ?? '—',
        ];

        if (! $detailed) {
            return $row;
        }

        $row['payment_info'] = [
            'status' => $expense->payment_status,
            'status_label' => $row['payment_status_label'],
            'date' => $row['payment_date_formatted'],
            'method' => $expense->payment_status === 'paid' ? 'Banka Havalesi' : '—',
            'reference' => $expense->payment_status === 'paid'
                ? sprintf('ODM-%d-%04d', $expense->expense_date->year, $expense->id)
                : '—',
        ];

        $row['current_account_movement'] = $expense->current_account_id ? [
            'code' => $expense->currentAccount?->code,
            'document_no' => $expense->document_no ?? $expense->reference,
            'date' => $row['expense_date_formatted'],
            'type_label' => $expense->payment_status === 'paid' ? 'Ödeme' : 'Borç Dekontu',
            'debit' => $amount,
            'credit' => $expense->payment_status === 'paid' ? $amount : 0,
            'debit_formatted' => money_excl_vat($amount),
            'credit_formatted' => $expense->payment_status === 'paid' ? money_excl_vat($amount) : '—',
            'description' => 'Gider kaydı: '.$expense->reference,
        ] : null;

        $row['documents'] = [
            ['name' => ($expense->document_no ?? $expense->reference).'.pdf', 'type' => 'Fatura', 'date' => $row['expense_date_formatted']],
            ['name' => 'Ek-'.$expense->reference.'.pdf', 'type' => 'Ek Belge', 'date' => $row['created_at_formatted']],
        ];

        $row['payee_info'] = $expense->courier ? [
            'type' => 'Kurye',
            'name' => $expense->courier->full_name,
            'phone' => $expense->courier->phone ?? '—',
        ] : ($expense->agency ? [
            'type' => 'Acente',
            'name' => $expense->agency->company_name,
            'phone' => $expense->agency->phone ?? '—',
        ] : [
            'type' => '—',
            'name' => 'İlgili cari hesap yok',
            'phone' => '—',
        ]);

        return $row;
    }

    private function earningReference(FinanceExpense $expense): ?string
    {
        if ($expense->earning_line_id === null) {
            return null;
        }

        $prefix = $expense->expense_type === 'courier_earning' ? 'HKD' : 'AHK';

        return sprintf('%s-%d-%04d', $prefix, $expense->expense_date->year, $expense->earning_line_id);
    }
}
