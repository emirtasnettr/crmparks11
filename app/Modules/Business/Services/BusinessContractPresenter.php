<?php

namespace App\Modules\Business\Services;

use App\Models\Contract;
use App\Modules\Business\Models\Business;
use App\Support\ContractStatusResolver;
use App\Support\PublicMediaUrl;
use Carbon\Carbon;

class BusinessContractPresenter
{
    public function __construct(
        private readonly BusinessDocumentService $documents,
    ) {}

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
        return $this->enrich($contract);
    }

    public function displayStatus(Contract $contract): string
    {
        return $this->resolveDisplayStatus($contract);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(Contract $contract): array
    {
        $contract->loadMissing(['contractable', 'contractType']);

        /** @var Business|null $business */
        $business = $contract->contractable;
        $startDate = $contract->start_date ?? Carbon::today();
        $endDate = $contract->end_date ?? $startDate;
        $displayStatus = $this->resolveDisplayStatus($contract);
        $remainingDays = (int) Carbon::today()->diffInDays($endDate, false);
        $document = $this->documents->findForContract($contract);
        $actor = auth()->user();

        return [
            'id' => $contract->id,
            'uuid' => $contract->uuid,
            'business_id' => $business?->id,
            'business_name' => $business?->displayName() ?? '—',
            'business_brand' => $business?->brand_name ?? '—',
            'contract_number' => $contract->contract_number,
            'contract_type' => $contract->contractType?->code ?? '',
            'contract_type_label' => $contract->contractType?->label ?? '—',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_date_formatted' => $startDate->format('d.m.Y'),
            'end_date_formatted' => $endDate->format('d.m.Y'),
            'status' => $displayStatus,
            'stored_status' => $contract->status,
            'remaining_days' => $remainingDays,
            'is_current' => ContractStatusResolver::isCurrent($displayStatus),
            'can_update' => $contract->status !== 'cancelled',
            'can_delete' => $actor?->hasRole('super_admin') ?? false,
            'notes' => $contract->notes,
            'document_id' => $document?->id,
            'file_name' => $document?->original_name,
            'file_url' => PublicMediaUrl::fromPath($document?->file_path),
        ];
    }

    private function resolveDisplayStatus(Contract $contract): string
    {
        $startDate = $contract->start_date ?? Carbon::today();
        $endDate = $contract->end_date ?? $startDate;

        return ContractStatusResolver::resolve($contract->status, $startDate, $endDate);
    }
}
