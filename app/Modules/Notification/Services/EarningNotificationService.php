<?php

namespace App\Modules\Notification\Services;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Notification\Notifications\SystemNotification;

class EarningNotificationService
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function notifyCreated(EarningLine $earning, User $actor): void
    {
        $businessName = $earning->business?->company_name ?? 'İşletme';
        $period = $this->periodLabel($earning);

        $this->dispatcher->notifyRoles(
            ['super_admin', 'general_manager', 'finance_officer', 'operations_manager'],
            new SystemNotification(
                type: 'earning_created',
                title: 'Yeni Hakediş Kaydı',
                message: "{$businessName} için {$period} dönemi hakedişi oluşturuldu.",
                actionUrl: route('businesses.earnings.show', $earning->id),
                meta: [
                    'earning_id' => $earning->id,
                    'business_id' => $earning->business_id,
                    'actor_id' => $actor->id,
                ],
            ),
            except: $actor,
        );
    }

    public function notifyApproved(EarningLine $earning, User $actor): void
    {
        $businessName = $earning->business?->company_name ?? 'İşletme';
        $period = $this->periodLabel($earning);

        $this->dispatcher->notifyRoles(
            ['super_admin', 'general_manager', 'finance_officer'],
            new SystemNotification(
                type: 'earning_approved',
                title: 'Hakediş Onaylandı',
                message: "{$businessName} — {$period} dönemi hakedişi onaylandı ve finans kayıtları oluşturuldu.",
                actionUrl: route('businesses.earnings.show', $earning->id),
                meta: [
                    'earning_id' => $earning->id,
                    'business_id' => $earning->business_id,
                    'actor_id' => $actor->id,
                ],
            ),
            except: $actor,
        );
    }

    private function periodLabel(EarningLine $earning): string
    {
        return sprintf('%02d/%d', $earning->period_month, $earning->period_year);
    }
}
