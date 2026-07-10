<?php

namespace App\Modules\Agency\Services;

use App\Models\Contract;
use App\Models\ContractType;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgencyContractService
{
    public function __construct(
        private readonly AgencyContractPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Contract>
     */
    public function filter(array $filters): Collection
    {
        $contracts = $this->baseQuery($filters)
            ->with(['contractable', 'contractType', 'creator'])
            ->orderByDesc('end_date')
            ->get();

        return $this->applyPresentationFilters($contracts, $filters)
            ->sortByDesc(fn (Contract $contract) => $this->presenter->indexRow($contract)['is_current'] ? 1 : 0)
            ->values();
    }

    /**
     * @return Collection<int, Contract>
     */
    public function forAgency(int $agencyId): Collection
    {
        return Contract::query()
            ->where('contractable_type', Agency::class)
            ->where('contractable_id', $agencyId)
            ->with(['contractable', 'contractType'])
            ->orderByDesc('end_date')
            ->get();
    }

    public function find(int $id): ?Contract
    {
        return Contract::query()
            ->where('contractable_type', Agency::class)
            ->with(['contractable', 'contractType', 'creator'])
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters)
            ->map(fn (Contract $contract) => $this->presenter->indexRow($contract));

        return [
            'total' => $items->count(),
            'active' => $items->where('status', 'active')->count(),
            'expiring_soon' => $items->where('status', 'expiring_soon')->count(),
            'expired' => $items->where('status', 'expired')->count(),
        ];
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
    public function create(array $data, User $user): Contract
    {
        return DB::transaction(function () use ($data, $user): Contract {
            $agency = Agency::query()->findOrFail((int) $data['agency_id']);
            $contractType = ContractType::query()
                ->where('code', $data['contract_type'])
                ->firstOrFail();

            $title = $contractType->label;
            if (! empty($data['contract_number'])) {
                $title .= ' - '.$data['contract_number'];
            }

            return Contract::query()->create([
                'contractable_type' => Agency::class,
                'contractable_id' => $agency->id,
                'contract_type_id' => $contractType->id,
                'title' => $title,
                'contract_number' => $data['contract_number'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'auto_reminder' => ! empty($data['auto_renewal']),
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }

    public function deactivate(Contract $contract): Contract
    {
        $contract->update(['status' => 'cancelled']);

        return $contract->fresh(['contractable', 'contractType', 'creator']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return Contract::query()
            ->where('contractable_type', Agency::class)
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('contractable_id', (int) $filters['agency_id']);
            })
            ->when(! empty($filters['contract_type']) && $filters['contract_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('contractType', fn (Builder $type) => $type->where('code', $filters['contract_type']));
            })
            ->when(! empty($filters['start_date']) && $filters['start_date'] !== 'all', function (Builder $query) use ($filters): void {
                $today = Carbon::today();

                match ($filters['start_date']) {
                    'this_month' => $query
                        ->whereMonth('start_date', $today->month)
                        ->whereYear('start_date', $today->year),
                    'this_year' => $query->whereYear('start_date', $today->year),
                    'last_30_days' => $query->whereDate('start_date', '>=', $today->copy()->subDays(30)),
                    default => null,
                };
            })
            ->when(! empty($filters['end_date']) && $filters['end_date'] !== 'all', function (Builder $query) use ($filters): void {
                $today = Carbon::today();

                match ($filters['end_date']) {
                    'expiring_soon' => $query
                        ->whereDate('end_date', '>=', $today)
                        ->whereDate('end_date', '<=', $today->copy()->addDays(30)),
                    'expired' => $query->whereDate('end_date', '<', $today),
                    'this_month' => $query
                        ->whereMonth('end_date', $today->month)
                        ->whereYear('end_date', $today->year),
                    default => null,
                };
            });
    }

    /**
     * @param  Collection<int, Contract>  $contracts
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Contract>
     */
    private function applyPresentationFilters(Collection $contracts, array $filters): Collection
    {
        if (empty($filters['status']) || $filters['status'] === 'all') {
            return $contracts;
        }

        return $contracts
            ->filter(fn (Contract $contract) => $this->presenter->displayStatus($contract) === $filters['status'])
            ->values();
    }
}
