<?php

namespace App\Modules\Business\Services;

use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessContactService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, BusinessContact>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with('business')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, BusinessContact>
     */
    public function forBusiness(int $businessId): Collection
    {
        return BusinessContact::query()
            ->where('business_id', $businessId)
            ->orderByDesc('is_default')
            ->orderBy('full_name')
            ->get();
    }

    public function find(int $id): ?BusinessContact
    {
        return BusinessContact::query()
            ->with('business')
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
    public function create(array $data): BusinessContact
    {
        return DB::transaction(function () use ($data): BusinessContact {
            if (! empty($data['is_default'])) {
                $this->clearDefaultForBusiness((int) $data['business_id']);
            }

            return BusinessContact::query()->create($this->attributes($data));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BusinessContact $contact, array $data): BusinessContact
    {
        return DB::transaction(function () use ($contact, $data): BusinessContact {
            if (! empty($data['is_default'])) {
                $this->clearDefaultForBusiness((int) $contact->business_id, $contact->id);
            }

            $contact->update($this->attributes($data, $contact));

            return $contact->fresh(['business']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return BusinessContact::query()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', ['%'.$search.'%']);
                });
            })
            ->when(! empty($filters['business_id']) && $filters['business_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('business_id', (int) $filters['business_id']);
            })
            ->when(! empty($filters['title']) && $filters['title'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('title', $filters['title']);
            })
            ->when(
                Schema::hasColumn('business_contacts', 'status')
                && ! empty($filters['status'])
                && $filters['status'] !== 'all',
                function (Builder $query) use ($filters): void {
                    $query->where('status', $filters['status']);
                },
            );
    }

    private function clearDefaultForBusiness(int $businessId, ?int $exceptId = null): void
    {
        BusinessContact::query()
            ->where('business_id', $businessId)
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->update(['is_default' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?BusinessContact $contact = null): array
    {
        $attributes = [
            'full_name' => $data['full_name'],
            'title' => $data['title'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'is_default' => ! empty($data['is_default']),
            'notes' => $data['notes'] ?? $contact?->notes,
        ];

        if ($contact === null) {
            $attributes['business_id'] = (int) $data['business_id'];
        }

        if (Schema::hasColumn('business_contacts', 'status')) {
            $attributes['status'] = $data['status'] ?? $contact?->status ?? 'active';
        }

        return $attributes;
    }
}
