<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Data\CurrentAccountFormData;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurrentAccountService
{
    public function __construct(
        private readonly CurrentAccountPresenter $presenter,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['movements', 'accountable'])
            ->orderBy('code')
            ->get()
            ->map(fn (CurrentAccount $account) => $this->presenter->indexRow($account))
            ->when(
                ($filters['balance_status'] ?? 'all') !== 'all',
                fn (Collection $accounts) => $accounts->filter(
                    fn (array $account) => $account['balance_status'] === $filters['balance_status']
                )
            )
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summarize(array $filters): array
    {
        $accounts = $this->filter($filters);

        $totalReceivable = $accounts->where('balance', '>', 0)->sum('balance');
        $totalPayable = abs($accounts->where('balance', '<', 0)->sum('balance'));

        return [
            'count' => $accounts->count(),
            'total_receivable' => round($totalReceivable, 2),
            'total_payable' => round($totalPayable, 2),
            'net_balance' => round($accounts->sum('balance'), 2),
            'overdue_receivable' => round($accounts->sum('overdue_receivable'), 2),
            'overdue_payable' => round($accounts->sum('overdue_payable'), 2),
        ];
    }

    /**
     * @return array<int, array{id: int, code: string, title: string, type: string}>
     */
    public function options(?string $accountType = null): array
    {
        return CurrentAccount::query()
            ->when(
                $accountType !== null && $accountType !== 'all',
                fn (Builder $query) => $query->where('account_type', $accountType)
            )
            ->orderBy('code')
            ->get(['id', 'code', 'title', 'account_type'])
            ->map(fn (CurrentAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'title' => $account->title,
                'type' => $account->account_type,
                'type_label' => CurrentAccountFormData::accountTypes()[$account->account_type] ?? '—',
            ])
            ->all();
    }

    public function find(int $id): ?CurrentAccount
    {
        return CurrentAccount::query()
            ->with(['movements', 'accountable'])
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): CurrentAccount
    {
        return DB::transaction(function () use ($data, $user): CurrentAccount {
            $account = CurrentAccount::query()->create([
                'account_type' => $data['type'],
                'title' => $data['title'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            $account->update([
                'code' => $this->generateCode($account->id),
            ]);

            $this->activityLog->log(
                'current_account_created',
                $account,
                description: "{$account->code} cari hesabı oluşturuldu.",
            );

            return $account->fresh('movements');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $user): CurrentAccount
    {
        return DB::transaction(function () use ($id, $data, $user): CurrentAccount {
            $account = $this->find($id);

            if ($account === null) {
                abort(404);
            }

            if (! $this->canUpdate($account)) {
                throw ValidationException::withMessages([
                    'current_account' => 'Bu cari hesap güncellenemez.',
                ]);
            }

            $oldValues = $account->only([
                'title', 'phone', 'email', 'tax_number', 'city', 'address', 'status',
            ]);

            $updates = [
                'title' => $data['title'],
                'phone' => $data['phone'] ?? $account->phone,
                'email' => $data['email'] ?? $account->email,
                'tax_number' => $data['tax_number'] ?? $account->tax_number,
                'city' => $data['city'] ?? $account->city,
                'address' => $data['address'] ?? $account->address,
            ];

            if ($account->accountable_type === null) {
                $updates['account_type'] = $data['type'] ?? $account->account_type;
            }

            if (isset($data['status'])) {
                $updates['status'] = $data['status'];
            }

            $account->update($updates);

            $this->activityLog->log(
                'current_account_updated',
                $account,
                description: "{$account->code} cari hesabı güncellendi.",
                oldValues: $oldValues,
                newValues: $account->fresh()->only(array_keys($oldValues)),
            );

            return $account->fresh('movements');
        });
    }

    public function canUpdate(CurrentAccount $account): bool
    {
        return true;
    }

    public function deactivate(int $id, User $user): CurrentAccount
    {
        return DB::transaction(function () use ($id, $user): CurrentAccount {
            $account = $this->find($id);

            if ($account === null) {
                abort(404);
            }

            if ($account->status === 'passive') {
                throw ValidationException::withMessages([
                    'current_account' => 'Bu cari hesap zaten pasif.',
                ]);
            }

            $oldStatus = $account->status;

            $account->update(['status' => 'passive']);

            $this->activityLog->log(
                'current_account_deactivated',
                $account,
                description: "{$account->code} cari hesabı pasife alındı.",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'passive'],
            );

            return $account->fresh('movements');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createMovement(array $data, User $user): CurrentAccountMovement
    {
        return DB::transaction(function () use ($data, $user): CurrentAccountMovement {
            $account = CurrentAccount::query()->findOrFail((int) $data['current_account_id']);
            $amount = round((float) $data['amount'], 2);
            $sides = CurrentAccountFormData::movementSides()[$data['type']] ?? null;

            if ($sides === null) {
                throw new \InvalidArgumentException('Geçersiz hareket türü.');
            }

            $movement = CurrentAccountMovement::query()->create([
                'current_account_id' => $account->id,
                'transaction_date' => $data['transaction_date'],
                'document_no' => $data['document_no'] ?? null,
                'type' => $data['type'],
                'debit' => $sides['debit'] ? $amount : 0,
                'credit' => $sides['credit'] ? $amount : 0,
                'description' => $data['description'] ?? null,
                'related_type' => $data['related_type'] ?? null,
                'related_id' => isset($data['related_id']) ? (int) $data['related_id'] : null,
                'created_by' => $user->id,
            ]);

            $this->activityLog->log(
                'current_account_movement_created',
                $movement,
                description: "{$account->code} cari hesabına hareket eklendi.",
            );

            return $movement;
        });
    }

    public function ensureForEntity(Model $entity): CurrentAccount
    {
        $mapping = $this->entityMapping($entity);

        $existing = CurrentAccount::query()
            ->where('accountable_type', $mapping['accountable_type'])
            ->where('accountable_id', $entity->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($entity, $mapping): CurrentAccount {
            $account = CurrentAccount::query()->create([
                'account_type' => $mapping['account_type'],
                'accountable_type' => $mapping['accountable_type'],
                'accountable_id' => $entity->getKey(),
                'title' => $mapping['title'],
                'phone' => $mapping['phone'],
                'email' => $mapping['email'],
                'tax_number' => $mapping['tax_number'],
                'city' => $mapping['city'],
                'address' => $mapping['address'],
                'status' => $mapping['status'],
            ]);

            $account->update([
                'code' => $this->generateCode($account->id),
            ]);

            return $account;
        });
    }

    public function syncMissingEntityAccounts(): int
    {
        $created = 0;

        Business::query()->each(function (Business $business) use (&$created): void {
            if ($this->hasAccountForEntity($business)) {
                return;
            }

            $this->ensureForEntity($business);
            $created++;
        });

        Courier::query()->each(function (Courier $courier) use (&$created): void {
            if ($this->hasAccountForEntity($courier)) {
                return;
            }

            $this->ensureForEntity($courier);
            $created++;
        });

        Agency::query()->each(function (Agency $agency) use (&$created): void {
            if ($this->hasAccountForEntity($agency)) {
                return;
            }

            $this->ensureForEntity($agency);
            $created++;
        });

        return $created;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));

        return CurrentAccount::query()
            ->when(
                ($filters['type'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('account_type', $filters['type'])
            )
            ->when(
                ($filters['status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', ["%{$search}%"])
                        ->orWhere(function (Builder $brandQuery) use ($search): void {
                            $brandQuery->where('account_type', 'business')
                                ->whereHasMorph('accountable', [Business::class], function (Builder $business) use ($search): void {
                                    $business->whereRaw('LOWER(COALESCE(brand_name, \'\')) LIKE ?', ["%{$search}%"])
                                        ->orWhereRaw('LOWER(COALESCE(company_name, \'\')) LIKE ?', ["%{$search}%"]);
                                });
                        });
                });
            });
    }

    private function hasAccountForEntity(Model $entity): bool
    {
        $mapping = $this->entityMapping($entity);

        return CurrentAccount::query()
            ->where('accountable_type', $mapping['accountable_type'])
            ->where('accountable_id', $entity->getKey())
            ->exists();
    }

    /**
     * @return array{
     *     accountable_type: class-string,
     *     account_type: string,
     *     title: string,
     *     phone: ?string,
     *     email: ?string,
     *     tax_number: ?string,
     *     city: ?string,
     *     address: ?string,
     *     status: string
     * }
     */
    private function entityMapping(Model $entity): array
    {
        if ($entity instanceof Business) {
            $entity->loadMissing(['city']);

            return [
                'accountable_type' => Business::class,
                'account_type' => 'business',
                'title' => $entity->company_name,
                'phone' => $entity->phone,
                'email' => $entity->email,
                'tax_number' => $entity->tax_number,
                'city' => $entity->city?->name,
                'address' => $entity->address,
                'status' => $entity->status === 'active' ? 'active' : 'passive',
            ];
        }

        if ($entity instanceof Courier) {
            $entity->loadMissing(['city']);

            return [
                'accountable_type' => Courier::class,
                'account_type' => 'courier',
                'title' => $entity->full_name,
                'phone' => $entity->phone,
                'email' => $entity->email,
                'tax_number' => $entity->tc_number,
                'city' => $entity->city?->name,
                'address' => $entity->address,
                'status' => $entity->status === 'active' ? 'active' : 'passive',
            ];
        }

        if ($entity instanceof Agency) {
            $entity->loadMissing(['city']);

            return [
                'accountable_type' => Agency::class,
                'account_type' => 'agency',
                'title' => $entity->company_name,
                'phone' => $entity->phone,
                'email' => $entity->email,
                'tax_number' => $entity->tax_number,
                'city' => $entity->city?->name,
                'address' => $entity->address,
                'status' => $entity->status === 'active' ? 'active' : 'passive',
            ];
        }

        throw new \InvalidArgumentException('Desteklenmeyen cari hesap kaynağı.');
    }

    private function generateCode(int $id): string
    {
        return sprintf('CAR-%06d', $id);
    }
}
