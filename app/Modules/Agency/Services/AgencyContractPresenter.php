<?php

namespace App\Modules\Agency\Services;

use App\Models\Contract;
use App\Support\ContractStatusResolver;
use Carbon\Carbon;

class AgencyContractPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(Contract $contract): array
    {
        return $this->enrich($contract);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(Contract $contract): array
    {
        return array_merge($this->enrich($contract), [
            'attachments' => [],
            'activity_log' => [],
        ]);
    }

    public function displayStatus(Contract $contract): string
    {
        $startDate = $contract->start_date ?? Carbon::today();
        $endDate = $contract->end_date ?? $startDate;

        return ContractStatusResolver::resolve($contract->status, $startDate, $endDate);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(Contract $contract): array
    {
        $contract->loadMissing(['contractable', 'contractType']);

        $agency = $contract->contractable;
        $startDate = $contract->start_date ?? Carbon::today();
        $endDate = $contract->end_date ?? $startDate;
        $displayStatus = $this->displayStatus($contract);
        $remainingDays = (int) Carbon::today()->diffInDays($endDate, false);

        return [
            'id' => $contract->id,
            'uuid' => $contract->uuid,
            'agency_id' => $agency?->id,
            'agency_name' => $agency?->displayName() ?? '—',
            'contract_number' => $contract->contract_number,
            'contract_type' => $contract->contractType?->code ?? '',
            'contract_type_label' => $contract->contractType?->label ?? '—',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_date_formatted' => $startDate->format('d.m.Y'),
            'end_date_formatted' => $endDate->format('d.m.Y'),
            'status' => $displayStatus,
            'remaining_days' => $remainingDays,
            'is_current' => ContractStatusResolver::isCurrent($displayStatus),
            'auto_renewal' => (bool) $contract->auto_reminder,
            'notes' => $contract->notes,
            'file_name' => null,
        ];
    }
}
