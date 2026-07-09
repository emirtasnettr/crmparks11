<?php

namespace App\Modules\Courier\Services;

use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourierBankAccountService
{
    public function __construct(
        private readonly CourierBankAccountPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CourierBankAccount>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with('courier')
            ->get()
            ->sortByDesc(fn (CourierBankAccount $account) => sprintf(
                '%d-%d-%03d',
                $account->is_default ? 1 : 0,
                $account->status === 'active' ? 1 : 0,
                $account->id,
            ))
            ->values();
    }

    public function find(int $id): ?CourierBankAccount
    {
        return CourierBankAccount::query()
            ->with('courier')
            ->find($id);
    }

    /**
     * @return Collection<int, CourierBankAccount>
     */
    public function forCourier(int $courierId): Collection
    {
        return CourierBankAccount::query()
            ->where('courier_id', $courierId)
            ->with('courier')
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $items = CourierBankAccount::query()->get();

        return [
            'count' => $items->count(),
            'active' => $items->where('status', 'active')->count(),
            'default' => $items->where('is_default', true)->count(),
            'inactive' => $items->where('status', 'inactive')->count(),
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
     * @return array<int, array{courier_id: int, default_count: int}>
     */
    public function defaultAccountViolations(): array
    {
        return CourierBankAccount::query()
            ->where('is_default', true)
            ->get()
            ->groupBy('courier_id')
            ->map(fn ($group, $courierId) => [
                'courier_id' => (int) $courierId,
                'default_count' => $group->count(),
            ])
            ->where('default_count', '>', 1)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CourierBankAccount
    {
        return DB::transaction(function () use ($data): CourierBankAccount {
            $courierId = (int) $data['courier_id'];
            $isDefault = ! empty($data['is_default']);

            if ($isDefault) {
                $this->clearDefaultForCourier($courierId);
            }

            return CourierBankAccount::query()->create([
                'courier_id' => $courierId,
                'bank_key' => $data['bank_key'],
                'account_holder' => $data['account_holder'],
                'iban' => $this->normalizeIban($data['iban']),
                'branch_code' => $data['branch_code'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'is_default' => $isDefault,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return CourierBankAccount::query()
            ->when(! empty($filters['courier_id']) && $filters['courier_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('courier_id', (int) $filters['courier_id']);
            })
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(account_holder) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(iban) LIKE ?', ['%'.$search.'%'])
                        ->orWhereHas('courier', fn (Builder $courier) => $courier->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%']));
                });
            })
            ->when(! empty($filters['bank_key']) && $filters['bank_key'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('bank_key', $filters['bank_key']);
            })
            ->when(! empty($filters['is_default']) && $filters['is_default'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('is_default', $filters['is_default'] === 'yes');
            })
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            });
    }

    private function clearDefaultForCourier(int $courierId, ?int $exceptId = null): void
    {
        CourierBankAccount::query()
            ->where('courier_id', $courierId)
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->update(['is_default' => false]);
    }

    private function normalizeIban(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', $iban) ?? $iban);
    }
}
