<?php

namespace App\Modules\Courier\Services;

use App\Models\Document;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\ActivityLog\Support\ActivityChangeFormatter;
use App\Modules\Courier\Data\CourierActivityFormData;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use App\Modules\Courier\Models\CourierVehicle;

class CourierActivityPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(ActivityLog $log): array
    {
        return $this->enrich($log);
    }

    public function resolveCourier(ActivityLog $log): ?Courier
    {
        $log->loadMissing(['subject']);

        $subject = $log->subject;

        if ($subject instanceof Courier) {
            return $subject;
        }

        if ($subject instanceof CourierVehicle || $subject instanceof CourierBankAccount) {
            $subject->loadMissing('courier');

            return $subject->courier;
        }

        if ($subject instanceof Document) {
            $subject->loadMissing('documentable');

            if ($subject->documentable instanceof Courier) {
                return $subject->documentable;
            }
        }

        return null;
    }

    public function resolveCourierId(ActivityLog $log): ?int
    {
        return $this->resolveCourier($log)?->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(ActivityLog $log): array
    {
        $log->loadMissing(['user', 'subject']);
        $courier = $this->resolveCourier($log);
        $occurredAt = $log->created_at ?? now();

        return [
            'id' => $log->id,
            'uuid' => $log->uuid,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'occurred_at_formatted' => $occurredAt->format('d.m.Y H:i'),
            'occurred_at_date' => $occurredAt->format('d.m.Y'),
            'occurred_at_time' => $occurredAt->format('H:i'),
            'courier_id' => $courier?->id,
            'courier_name' => $courier?->full_name ?? '—',
            'action' => $log->action,
            'action_label' => CourierActivityFormData::actionTypes()[$log->action] ?? $log->action,
            'user_id' => $log->user_id,
            'user_name' => $log->user?->name ?? '—',
            'ip_address' => $log->ip_address ?? '—',
            'user_agent' => $log->user_agent ?? '—',
            'description' => $log->description ?? '—',
            'old_value' => $this->formatChangeValue($log->old_values),
            'new_value' => $this->formatChangeValue($log->new_values),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $values
     */
    private function formatChangeValue(?array $values): ?string
    {
        return ActivityChangeFormatter::toDisplayString($values);
    }
}
