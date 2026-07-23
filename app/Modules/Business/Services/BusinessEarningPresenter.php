<?php

namespace App\Modules\Business\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Setting\Services\SettingsManager;
use App\Support\EarningStatusMapper;

class BusinessEarningPresenter
{
    public function __construct(
        private readonly SettingsManager $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexRow(EarningLine $line): array
    {
        return $this->enrich($line);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(EarningLine $line): array
    {
        return $this->enrich($line);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(EarningLine $line): array
    {
        $line->loadMissing(['business', 'courier.agency', 'status']);

        $months = BusinessEarningFormData::months();
        $statusCode = EarningStatusMapper::toUiCode($line->status?->code ?? 'draft');
            $courierPayment = (float) $line->net_courier_payment;
            $extraExpense = (float) $line->extra_expense;
            $revenue = round((float) $line->revenue_total + (float) $line->extra_payment, 2);
            $profit = (float) $line->profit;
        $pricingModel = $line->pricing_model ?: 'per_package';

        return [
            'id' => $line->id,
            'business_id' => $line->business_id,
            'courier_id' => $line->courier_id,
            'agency_id' => $line->courier?->agency_id,
            'business_name' => $line->business?->displayName() ?? '—',
            'courier_name' => $line->courier?->full_name ?? '—',
            'agency_name' => $line->courier?->agency?->displayName() ?? '—',
            'period_month' => $line->period_month,
            'period_year' => $line->period_year,
            'work_date' => $line->work_date?->toDateString(),
            'period_label' => $line->work_date
                ? $line->work_date->format('d.m.Y')
                : trim(($months[$line->period_month] ?? '').' '.$line->period_year),
            'pricing_model' => $pricingModel,
            'pricing_model_label' => BusinessEarningFormData::pricingModels()[$pricingModel] ?? $pricingModel,
            'package_count' => (int) $line->package_count,
            'worked_hours' => $line->resolvedWorkedHours(),
            'revenue_unit_price' => (float) $line->revenue_unit_price,
            'courier_unit_price' => (float) $line->courier_unit_price,
            'extra_income' => (float) $line->extra_payment,
            'extra_expense' => $extraExpense,
            'deduction' => (float) $line->deduction,
            'status' => $statusCode,
            'status_label' => BusinessEarningFormData::statuses()[$statusCode] ?? $statusCode,
            'description' => $line->description,
            'revenue' => $revenue,
            'courier_payment' => $courierPayment,
            'total_expense' => round($courierPayment + $extraExpense, 2),
            'profit' => $profit,
            'revenue_formatted' => MoneyCalculator::format($revenue),
            'courier_payment_formatted' => MoneyCalculator::format($courierPayment),
            'total_expense_formatted' => MoneyCalculator::format($courierPayment + $extraExpense),
            'profit_formatted' => MoneyCalculator::format($profit),
            'can_update' => $this->canUpdate($statusCode),
            'can_approve' => $this->canApprove($line, $statusCode),
            'can_delete' => $this->canDelete($statusCode),
            'needs_second_approval' => $this->needsSecondApproval($line),
        ];
    }

    private function canUpdate(string $statusCode): bool
    {
        return ! in_array($statusCode, ['paid', 'cancelled'], true);
    }

    private function canApprove(EarningLine $line, string $statusCode): bool
    {
        if (! in_array($statusCode, ['draft', 'pending'], true)) {
            return false;
        }

        if ($this->approvalProcess() !== 'dual' || $line->first_approved_by === null) {
            return true;
        }

        $userId = auth()->id();

        return $userId !== null && (int) $line->first_approved_by !== (int) $userId;
    }

    private function needsSecondApproval(EarningLine $line): bool
    {
        return $this->approvalProcess() === 'dual'
            && $line->first_approved_by !== null
            && in_array($line->status?->code, ['draft', 'pending_review'], true);
    }

    private function canDelete(string $statusCode): bool
    {
        return $this->canUpdate($statusCode);
    }

    private function approvalProcess(): string
    {
        $process = $this->settings->group('earnings')->all()['approval_process'] ?? 'auto';

        return in_array($process, ['single', 'dual', 'auto'], true) ? $process : 'auto';
    }
}
