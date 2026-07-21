<?php

namespace App\Modules\Agency\Services;

use App\Models\Contract;
use App\Models\Document;
use App\Models\EarningLine;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\ActivityLog\Support\ActivityChangeFormatter;
use App\Modules\Agency\Data\AgencyActivityFormData;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceExpense;

class AgencyActivityPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(ActivityLog $log): array
    {
        return $this->enrich($log);
    }

    public function resolveAgency(ActivityLog $log): ?Agency
    {
        $log->loadMissing(['subject']);

        $subject = $log->subject;

        if ($subject instanceof Agency) {
            return $subject;
        }

        if ($subject instanceof AgencyContact || $subject instanceof FinanceExpense) {
            $subject->loadMissing('agency');

            return $subject->agency;
        }

        if ($subject instanceof Courier) {
            $subject->loadMissing('agency');

            return $subject->agency;
        }

        if ($subject instanceof EarningLine) {
            $subject->loadMissing('courier.agency');

            return $subject->courier?->agency;
        }

        if ($subject instanceof Contract) {
            $subject->loadMissing('contractable');

            if ($subject->contractable instanceof Agency) {
                return $subject->contractable;
            }
        }

        if ($subject instanceof Document) {
            $subject->loadMissing('documentable');

            if ($subject->documentable instanceof Agency) {
                return $subject->documentable;
            }
        }

        return null;
    }

    public function resolveAgencyId(ActivityLog $log): ?int
    {
        return $this->resolveAgency($log)?->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(ActivityLog $log): array
    {
        $log->loadMissing(['user', 'subject']);
        $agency = $this->resolveAgency($log);
        $occurredAt = $log->created_at ?? now();
        $oldValue = $this->formatChangeValue($log->old_values);
        $newValue = $this->formatChangeValue($log->new_values);

        return [
            'id' => $log->id,
            'uuid' => $log->uuid,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'occurred_at_formatted' => $occurredAt->format('d.m.Y H:i'),
            'occurred_at_date' => $occurredAt->format('d.m.Y'),
            'occurred_at_time' => $occurredAt->format('H:i'),
            'agency_id' => $agency?->id,
            'agency_name' => $agency?->displayName() ?? '—',
            'action' => $log->action,
            'action_label' => AgencyActivityFormData::actionTypes()[$log->action] ?? $log->action,
            'user_id' => $log->user_id,
            'user_name' => $log->user?->name ?? '—',
            'ip_address' => $log->ip_address ?? '—',
            'user_agent' => $log->user_agent ?? '—',
            'description' => $log->description ?? '—',
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'subject_type' => 'agency',
            'subject_id' => $agency?->id,
            'causer_id' => $log->user_id,
            'properties' => array_filter([
                'old' => $oldValue,
                'attributes' => $newValue,
            ]),
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
