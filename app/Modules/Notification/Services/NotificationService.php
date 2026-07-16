<?php

namespace App\Modules\Notification\Services;

use App\Models\Contract;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Support\BusinessCardVisibility;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(
        private readonly NotificationPresenter $presenter,
    ) {}

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function index(User $user, array $filters, int $page = 1, int $perPage = 25): array
    {
        $notifications = $this->applyFilters($user->notifications()->latest(), $filters)->get();
        $rows = $notifications->map(fn (DatabaseNotification $notification) => $this->presenter->row($notification))->values();

        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return [
            'notifications' => $items,
            'summary' => $this->summarize($rows),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * @return array{unread_count: int, items: array<int, array<string, mixed>>}
     */
    public function headerPreview(User $user, int $limit = 5): array
    {
        $notifications = $user->unreadNotifications()->latest()->limit($limit)->get();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications
                ->map(fn (DatabaseNotification $notification) => $this->presenter->row($notification))
                ->values()
                ->all(),
        ];
    }

    public function markAsRead(User $user, string $id): void
    {
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification === null) {
            abort(404);
        }

        $notification->markAsRead();
    }

    public function open(User $user, string $id): string
    {
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification === null) {
            abort(404);
        }

        $notification->markAsRead();

        return $this->resolveDestination($notification, $user);
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(User $user, string $id): void
    {
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification === null) {
            abort(404);
        }

        $notification->delete();
    }

    private function resolveDestination(DatabaseNotification $notification, User $user): string
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $type = (string) ($data['type'] ?? '');
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        if ($type === 'form_submission_created') {
            return $this->resolveFormSubmissionDestination($notification, $meta);
        }

        if ($type === 'contract_expiry') {
            return $this->resolveContractExpiryDestination($notification, $meta, $user);
        }

        return $this->normalizeStoredActionUrl($data['action_url'] ?? null)
            ?? route('notifications.index');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function resolveContractExpiryDestination(DatabaseNotification $notification, array $meta, User $user): string
    {
        $contract = $this->findContractForNotification($notification, $meta);

        if ($contract !== null && ! $contract->trashed()) {
            return $this->contractDestinationForUser($contract, $user);
        }

        if ($contract !== null && $contract->trashed()) {
            return $this->contractFallbackDestination($contract, $user);
        }

        $businessId = (int) ($meta['business_id'] ?? 0);
        if ($businessId > 0 && Business::query()->whereKey($businessId)->exists()) {
            return BusinessCardVisibility::canViewRestrictedTabs($user)
                ? route('businesses.contracts.index', ['business_id' => $businessId])
                : route('businesses.show', $businessId);
        }

        $agencyId = (int) ($meta['agency_id'] ?? 0);
        if ($agencyId > 0 && Agency::query()->whereKey($agencyId)->exists()) {
            return route('agencies.contracts.index', ['agency_id' => $agencyId]);
        }

        return route('businesses.contracts.index');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function findContractForNotification(DatabaseNotification $notification, array $meta): ?Contract
    {
        $contractId = (int) ($meta['contract_id'] ?? 0);

        if ($contractId > 0) {
            $contract = Contract::query()->withTrashed()->with('contractable')->find($contractId);

            if ($contract !== null) {
                return $contract;
            }
        }

        $data = is_array($notification->data) ? $notification->data : [];
        $stored = $this->normalizeStoredActionUrl($data['action_url'] ?? null);

        if ($stored !== null && preg_match('#/sozlesmeler/(\d+)(?:\?|$)#', $stored, $matches) === 1) {
            return Contract::query()->withTrashed()->with('contractable')->find((int) $matches[1]);
        }

        return null;
    }

    private function contractDestinationForUser(Contract $contract, User $user): string
    {
        if ($contract->contractable_type === Agency::class) {
            return route('agencies.contracts.show', $contract->id);
        }

        if (BusinessCardVisibility::canViewRestrictedTabs($user)) {
            return route('businesses.contracts.show', $contract->id);
        }

        return $this->contractFallbackDestination($contract, $user);
    }

    private function contractFallbackDestination(Contract $contract, User $user): string
    {
        if ($contract->contractable_type === Agency::class) {
            $agencyId = (int) $contract->contractable_id;

            return $agencyId > 0
                ? route('agencies.contracts.index', ['agency_id' => $agencyId])
                : route('agencies.contracts.index');
        }

        $businessId = (int) $contract->contractable_id;

        if ($businessId > 0 && Business::query()->whereKey($businessId)->exists()) {
            return BusinessCardVisibility::canViewRestrictedTabs($user)
                ? route('businesses.contracts.index', ['business_id' => $businessId])
                : route('businesses.show', $businessId);
        }

        return route('businesses.contracts.index');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function resolveFormSubmissionDestination(DatabaseNotification $notification, array $meta): string
    {
        $formId = (int) ($meta['form_id'] ?? 0);
        $submissionId = (int) ($meta['submission_id'] ?? 0);

        if ($formId > 0 && $submissionId > 0) {
            $exists = \App\Modules\FormBuilder\Models\FormSubmission::query()
                ->where('form_id', $formId)
                ->whereKey($submissionId)
                ->exists();

            if ($exists) {
                return route('form-applications.show', [
                    'formId' => $formId,
                    'submissionId' => $submissionId,
                ]);
            }
        }

        if ($formId > 0 && $notification->created_at !== null) {
            $query = \App\Modules\FormBuilder\Models\FormSubmission::query()
                ->where('form_id', $formId);

            if (! empty($meta['landing_page_id'])) {
                $query->where('landing_page_id', (int) $meta['landing_page_id']);
            }

            $match = $query
                ->whereBetween('submitted_at', [
                    $notification->created_at->copy()->subMinutes(15),
                    $notification->created_at->copy()->addMinutes(15),
                ])
                ->orderByDesc('id')
                ->first();

            if ($match !== null) {
                return route('form-applications.show', [
                    'formId' => $formId,
                    'submissionId' => $match->id,
                ]);
            }

            return route('form-applications.submissions', ['formId' => $formId]);
        }

        return route('form-applications.index');
    }

    private function normalizeStoredActionUrl(mixed $stored): ?string
    {
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        if (str_starts_with($stored, '/')) {
            return $stored;
        }

        $parts = parse_url($stored);
        $path = $parts['path'] ?? null;

        if (is_string($path) && $path !== '') {
            $query = isset($parts['query']) ? '?'.$parts['query'] : '';

            return $path.$query;
        }

        return $stored;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Relations\MorphMany<\Illuminate\Notifications\DatabaseNotification, \App\Models\User>  $query
     * @param  array<string, string>  $filters
     */
    private function applyFilters($query, array $filters)
    {
        if (($filters['status'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }

        if (($filters['status'] ?? 'all') === 'read') {
            $query->whereNotNull('read_at');
        }

        if (($filters['type'] ?? 'all') !== 'all') {
            $type = $filters['type'];
            $query->where('data->type', $type);
        }

        if (($filters['date_range'] ?? 'all') !== 'all') {
            $from = match ($filters['date_range']) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => null,
            };

            if ($from instanceof Carbon) {
                $query->where('created_at', '>=', $from);
            }
        }

        return $query;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summarize(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'unread' => $rows->where('is_read', false)->count(),
            'read' => $rows->where('is_read', true)->count(),
            'today' => $rows->filter(function (array $row): bool {
                if (empty($row['created_at'])) {
                    return false;
                }

                return Carbon::parse($row['created_at'])->isToday();
            })->count(),
        ];
    }
}
