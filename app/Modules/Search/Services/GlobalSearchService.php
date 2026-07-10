<?php

namespace App\Modules\Search\Services;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * @return array{query: string, groups: array<int, array<string, mixed>>, total: int}
     */
    public function search(User $user, string $query, int $limitPerGroup = 5): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [
                'query' => $query,
                'groups' => [],
                'total' => 0,
            ];
        }

        $groups = [];

        if ($user->can('business.view') || $user->can('business.view_own')) {
            $items = $this->searchBusinesses($query, $limitPerGroup);
            if ($items->isNotEmpty()) {
                $groups[] = [
                    'key' => 'businesses',
                    'label' => 'İşletmeler',
                    'items' => $items->all(),
                ];
            }
        }

        if ($user->can('courier.view') || $user->can('courier.view_own')) {
            $items = $this->searchCouriers($query, $limitPerGroup);
            if ($items->isNotEmpty()) {
                $groups[] = [
                    'key' => 'couriers',
                    'label' => 'Kuryeler',
                    'items' => $items->all(),
                ];
            }
        }

        if ($user->can('agency.view') || $user->can('agency.view_own')) {
            $items = $this->searchAgencies($query, $limitPerGroup);
            if ($items->isNotEmpty()) {
                $groups[] = [
                    'key' => 'agencies',
                    'label' => 'Acenteler',
                    'items' => $items->all(),
                ];
            }
        }

        return [
            'query' => $query,
            'groups' => $groups,
            'total' => collect($groups)->sum(fn (array $group) => count($group['items'])),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchBusinesses(string $query, int $limit): Collection
    {
        $needle = '%'.mb_strtolower($query).'%';

        return Business::query()
            ->with(['city', 'district'])
            ->where(function ($q) use ($needle): void {
                $q->whereRaw('LOWER(company_name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(brand_name, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', [$needle]);
            })
            ->orderBy('company_name')
            ->limit($limit)
            ->get()
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'title' => $business->company_name,
                'subtitle' => trim(($business->city?->name ?? '').' / '.($business->district?->name ?? ''), ' /') ?: ($business->phone ?? '—'),
                'url' => route('businesses.show', $business->id),
                'type' => 'business',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchCouriers(string $query, int $limit): Collection
    {
        $needle = '%'.mb_strtolower($query).'%';

        return Courier::query()
            ->with('agency')
            ->where(function ($q) use ($needle): void {
                $q->whereRaw('LOWER(full_name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', [$needle]);
            })
            ->orderBy('full_name')
            ->limit($limit)
            ->get()
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'title' => $courier->full_name,
                'subtitle' => $courier->agency?->company_name ?? ($courier->phone ?? 'Esnaf Kurye'),
                'url' => route('couriers.show', $courier->id),
                'type' => 'courier',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchAgencies(string $query, int $limit): Collection
    {
        $needle = '%'.mb_strtolower($query).'%';

        return Agency::query()
            ->with(['city', 'district'])
            ->where(function ($q) use ($needle): void {
                $q->whereRaw('LOWER(company_name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(authorized_person, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', [$needle]);
            })
            ->orderBy('company_name')
            ->limit($limit)
            ->get()
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'title' => $agency->company_name,
                'subtitle' => trim(($agency->city?->name ?? '').' / '.($agency->district?->name ?? ''), ' /') ?: ($agency->phone ?? '—'),
                'url' => route('agencies.show', $agency->id),
                'type' => 'agency',
            ]);
    }
}
