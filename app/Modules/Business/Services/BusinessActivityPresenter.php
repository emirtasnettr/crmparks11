<?php

namespace App\Modules\Business\Services;

use App\Models\Contract;
use App\Models\Document;
use App\Models\EarningLine;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Business\Data\BusinessActivityFormData;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceRevenue;

class BusinessActivityPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(ActivityLog $log): array
    {
        return $this->enrich($log);
    }

    public function resolveBusiness(ActivityLog $log): ?Business
    {
        $log->loadMissing(['subject']);

        $subject = $log->subject;

        if ($subject instanceof Business) {
            return $subject;
        }

        if ($subject instanceof BusinessContact
            || $subject instanceof BusinessCourierAssignment
            || $subject instanceof EarningLine
            || $subject instanceof FinanceRevenue
            || $subject instanceof FinanceCollection) {
            $subject->loadMissing('business');

            return $subject->business;
        }

        if ($subject instanceof Contract) {
            $subject->loadMissing('contractable');

            if ($subject->contractable instanceof Business) {
                return $subject->contractable;
            }
        }

        if ($subject instanceof Document) {
            $subject->loadMissing('documentable');

            if ($subject->documentable instanceof Business) {
                return $subject->documentable;
            }
        }

        return null;
    }

    public function resolveBusinessId(ActivityLog $log): ?int
    {
        return $this->resolveBusiness($log)?->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(ActivityLog $log): array
    {
        $log->loadMissing(['user', 'subject']);
        $business = $this->resolveBusiness($log);
        $occurredAt = $log->created_at ?? now();

        return [
            'id' => $log->id,
            'uuid' => $log->uuid,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'occurred_at_formatted' => $occurredAt->format('d.m.Y H:i'),
            'occurred_at_date' => $occurredAt->format('Y-m-d'),
            'business_id' => $business?->id,
            'business_name' => $business?->company_name ?? '—',
            'action' => $log->action,
            'action_label' => BusinessActivityFormData::actionTypes()[$log->action] ?? $log->action,
            'user_id' => $log->user_id,
            'user_name' => $log->user?->name ?? '—',
            'ip_address' => $log->ip_address ?? '—',
            'description' => $log->description ?? '—',
        ];
    }
}
