<?php

namespace App\Modules\Business\Services;

use App\Models\Contract;
use App\Models\ContractType;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessContractService
{
    public function __construct(
        private readonly BusinessContractPresenter $presenter,
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
    public function forBusiness(int $businessId): Collection
    {
        return Contract::query()
            ->where('contractable_type', Business::class)
            ->where('contractable_id', $businessId)
            ->with(['contractable', 'contractType'])
            ->orderByDesc('end_date')
            ->get();
    }

    public function find(int $id): ?Contract
    {
        return Contract::query()
            ->where('contractable_type', Business::class)
            ->with(['contractable', 'contractType', 'creator'])
            ->find($id);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->company_name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Contract
    {
        return DB::transaction(function () use ($data, $user): Contract {
            $business = Business::query()->findOrFail((int) $data['business_id']);
            $contractType = ContractType::query()
                ->where('code', $data['contract_type'])
                ->firstOrFail();

            $title = $contractType->label;
            if (! empty($data['contract_number'])) {
                $title .= ' - '.$data['contract_number'];
            }

            return Contract::query()->create([
                'contractable_type' => Business::class,
                'contractable_id' => $business->id,
                'contract_type_id' => $contractType->id,
                'title' => $title,
                'contract_number' => $data['contract_number'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return Contract::query()
            ->where('contractable_type', Business::class)
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(COALESCE(contract_number, "")) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(title) LIKE ?', ['%'.$search.'%'])
                        ->orWhereHasMorph('contractable', [Business::class], function (Builder $business) use ($search): void {
                            $business->whereRaw('LOWER(company_name) LIKE ?', ['%'.$search.'%']);
                        })
                        ->orWhereHas('contractType', function (Builder $type) use ($search): void {
                            $type->whereRaw('LOWER(label) LIKE ?', ['%'.$search.'%']);
                        });
                });
            })
            ->when(! empty($filters['business_id']) && $filters['business_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('contractable_id', (int) $filters['business_id']);
            })
            ->when(! empty($filters['contract_type']) && $filters['contract_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('contractType', fn (Builder $type) => $type->where('code', $filters['contract_type']));
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
