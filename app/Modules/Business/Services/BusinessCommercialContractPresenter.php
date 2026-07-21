<?php

namespace App\Modules\Business\Services;

use App\Modules\Business\Data\BusinessCommercialContractFormData;
use App\Modules\Business\Models\BusinessCommercialContract;
use App\Core\Helpers\MoneyCalculator;

class BusinessCommercialContractPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(BusinessCommercialContract $contract): array
    {
        $workTypes = BusinessCommercialContractFormData::workTypes();
        $periods = BusinessCommercialContractFormData::paymentPeriods();
        $statuses = BusinessCommercialContractFormData::statuses();

        return [
            'id' => $contract->id,
            'business_id' => $contract->business_id,
            'business_name' => $contract->business?->displayName() ?? '—',
            'start_date' => $contract->start_date?->toDateString(),
            'start_date_formatted' => $contract->start_date?->format('d.m.Y') ?? '—',
            'end_date' => $contract->end_date?->toDateString(),
            'end_date_formatted' => $contract->end_date?->format('d.m.Y') ?? '—',
            'work_type' => $contract->work_type,
            'work_type_label' => $workTypes[$contract->work_type] ?? $contract->work_type,
            'business_amount' => (float) $contract->business_amount,
            'business_amount_formatted' => MoneyCalculator::formatVatAmount((float) $contract->business_amount),
            'courier_amount' => (float) $contract->courier_amount,
            'courier_amount_formatted' => MoneyCalculator::formatVatAmount((float) $contract->courier_amount),
            'net_profit' => (float) $contract->net_profit,
            'net_profit_formatted' => MoneyCalculator::formatVatAmount((float) $contract->net_profit),
            'guaranteed_hourly_package_fee' => $contract->guaranteed_hourly_package_fee !== null
                ? (float) $contract->guaranteed_hourly_package_fee
                : null,
            'guaranteed_hourly_package_fee_formatted' => $contract->guaranteed_hourly_package_fee !== null
                ? MoneyCalculator::formatVatAmount((float) $contract->guaranteed_hourly_package_fee)
                : '—',
            'guaranteed_package_count' => $contract->guaranteed_package_count !== null
                ? (int) $contract->guaranteed_package_count
                : null,
            'payment_period' => $contract->payment_period,
            'payment_period_label' => $periods[$contract->payment_period] ?? $contract->payment_period,
            'status' => $contract->status,
            'status_label' => $statuses[$contract->status] ?? $contract->status,
            'is_active' => $contract->isActive(),
            'notes' => $contract->notes,
            'can_update' => $contract->isActive() && (auth()->user()?->hasRole('super_admin') ?? false),
            'show_url' => route('businesses.commercial-contracts.show', $contract->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(BusinessCommercialContract $contract): array
    {
        return $this->indexRow($contract);
    }
}
