<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgencyContactService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AgencyContact>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with('agency')
            ->orderByDesc('is_default')
            ->orderBy('full_name')
            ->get();
    }

    /**
     * @return Collection<int, AgencyContact>
     */
    public function forAgency(int $agencyId): Collection
    {
        return AgencyContact::query()
            ->where('agency_id', $agencyId)
            ->orderByDesc('is_default')
            ->orderBy('full_name')
            ->get();
    }

    public function find(int $id): ?AgencyContact
    {
        return AgencyContact::query()
            ->with('agency')
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters);

        return [
            'total' => $items->count(),
            'active' => $items->where('status', 'active')->count(),
            'default' => $items->where('is_default', true)->count(),
            'inactive' => $items->where('status', 'inactive')->count(),
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
    public function create(array $data): AgencyContact
    {
        return DB::transaction(function () use ($data): AgencyContact {
            if (! empty($data['is_default'])) {
                $this->clearDefaultForAgency((int) $data['agency_id']);
            }

            return AgencyContact::query()->create($this->attributes($data));
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return AgencyContact::query()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', ['%'.$search.'%']);
                });
            })
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('agency_id', (int) $filters['agency_id']);
            })
            ->when(! empty($filters['title']) && $filters['title'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('title', $filters['title']);
            })
            ->when(
                Schema::hasColumn('agency_contacts', 'status')
                && ! empty($filters['status'])
                && $filters['status'] !== 'all',
                function (Builder $query) use ($filters): void {
                    $query->where('status', $filters['status']);
                },
            );
    }

    private function clearDefaultForAgency(int $agencyId, ?int $exceptId = null): void
    {
        AgencyContact::query()
            ->where('agency_id', $agencyId)
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->update(['is_default' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?AgencyContact $contact = null): array
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
            $attributes['agency_id'] = (int) $data['agency_id'];
        }

        if (Schema::hasColumn('agency_contacts', 'status')) {
            $attributes['status'] = $data['status'] ?? $contact?->status ?? 'active';
        }

        return $attributes;
    }
}
