<?php

namespace App\Modules\Notification\Services;

use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Support\ContractStatusResolver;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;

class ScheduledReminderService
{
    private const CONTRACT_ROLES = ['super_admin', 'general_manager', 'operations_specialist'];

    private const FINANCE_ROLES = ['super_admin', 'general_manager'];

    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function sendContractExpiryReminders(): int
    {
        if (! $this->dispatcher->isTypeEnabled('contract_expiry')) {
            return 0;
        }

        $count = 0;
        $today = Carbon::today();

        Contract::query()
            ->whereNotNull('end_date')
            ->where('status', '!=', 'draft')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $today->copy()->addDays(30))
            ->orderBy('end_date')
            ->each(function (Contract $contract) use (&$count): void {
                $status = ContractStatusResolver::resolve(
                    $contract->status,
                    $contract->start_date,
                    $contract->end_date,
                );

                if ($status !== 'expiring_soon') {
                    return;
                }

                $reminderKey = "contract_expiry:{$contract->id}";

                if ($this->alreadySentToday('contract_expiry', $reminderKey)) {
                    return;
                }

                $title = $contract->title ?: 'Sözleşme';
                $endDate = $contract->end_date->format('d.m.Y');

                $queued = $this->dispatcher->notifyRoles(
                    self::CONTRACT_ROLES,
                    new SystemNotification(
                        type: 'contract_expiry',
                        title: 'Sözleşme Bitiş Hatırlatması',
                        message: "{$title} sözleşmesinin bitiş tarihi {$endDate}.",
                        actionUrl: $this->contractShowUrl($contract),
                        meta: [
                            'reminder_key' => $reminderKey,
                            'contract_id' => $contract->id,
                            'business_id' => $contract->contractable_type === Business::class
                                ? $contract->contractable_id
                                : null,
                            'agency_id' => $contract->contractable_type === Agency::class
                                ? $contract->contractable_id
                                : null,
                        ],
                    ),
                );

                $count += $queued;
            });

        return $count;
    }

    public function sendDocumentExpiryReminders(): int
    {
        if (! $this->dispatcher->isTypeEnabled('document_expiry')) {
            return 0;
        }

        $count = 0;
        $today = Carbon::today();

        Document::query()
            ->whereNotNull('expires_at')
            ->where(function ($query) use ($today): void {
                $query->whereBetween('expires_at', [$today, $today->copy()->addDays(30)])
                    ->orWhereBetween('expires_at', [$today->copy()->subDays(7), $today->copy()->subDay()]);
            })
            ->orderBy('expires_at')
            ->each(function (Document $document) use (&$count, $today): void {
                $reminderKey = "document_expiry:{$document->id}";

                if ($this->alreadySentToday('document_expiry', $reminderKey)) {
                    return;
                }

                $expiry = $document->expires_at->format('d.m.Y');
                $name = $document->original_name;
                $isExpired = $document->expires_at->startOfDay()->lt($today);

                $queued = $this->dispatcher->notifyRoles(
                    self::CONTRACT_ROLES,
                    new SystemNotification(
                        type: 'document_expiry',
                        title: $isExpired ? 'Evrak Süresi Doldu' : 'Evrak Süresi Yaklaşıyor',
                        message: $isExpired
                            ? "{$name} evrakının süresi {$expiry} tarihinde doldu."
                            : "{$name} evrakının süresi {$expiry} tarihinde dolacak.",
                        actionUrl: $this->documentShowUrl($document),
                        meta: [
                            'reminder_key' => $reminderKey,
                            'document_id' => $document->id,
                        ],
                    ),
                );

                $count += $queued;
            });

        return $count;
    }

    public function sendCollectionReminders(): int
    {
        if (! $this->dispatcher->isTypeEnabled('collection_reminder')) {
            return 0;
        }

        $count = 0;
        $today = Carbon::today();

        FinanceCollection::query()
            ->with('business')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereDate('due_date', '<=', $today->copy()->addDays(7))
            ->orderBy('due_date')
            ->each(function (FinanceCollection $collection) use (&$count, $today): void {
                $reminderKey = "collection_reminder:{$collection->id}";

                if ($this->alreadySentToday('collection_reminder', $reminderKey)) {
                    return;
                }

                $dueDate = $collection->due_date->format('d.m.Y');
                $businessName = $collection->business?->displayName() ?? 'İşletme';
                $isOverdue = $collection->due_date->lt($today);

                $queued = $this->dispatcher->notifyRoles(
                    self::FINANCE_ROLES,
                    new SystemNotification(
                        type: 'collection_reminder',
                        title: $isOverdue ? 'Geciken Tahsilat' : 'Tahsilat Hatırlatması',
                        message: "{$businessName} — {$collection->reference} tahsilatının vadesi {$dueDate}.",
                        actionUrl: route('finance.collections.show', $collection->id),
                        meta: [
                            'reminder_key' => $reminderKey,
                            'collection_id' => $collection->id,
                        ],
                    ),
                );

                $count += $queued;
            });

        return $count;
    }

    public function sendPaymentReminders(): int
    {
        if (! $this->dispatcher->isTypeEnabled('payment_reminder')) {
            return 0;
        }

        $count = 0;
        $today = Carbon::today();

        FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('scheduled_date', '<=', $today->copy()->addDays(7))
            ->orderBy('scheduled_date')
            ->each(function (FinancePayment $payment) use (&$count, $today): void {
                $reminderKey = "payment_reminder:{$payment->id}";

                if ($this->alreadySentToday('payment_reminder', $reminderKey)) {
                    return;
                }

                $scheduledDate = $payment->scheduled_date->format('d.m.Y');
                $recipient = $payment->recipient_name ?? 'Alıcı';
                $isOverdue = $payment->scheduled_date->lt($today);

                $queued = $this->dispatcher->notifyRoles(
                    self::FINANCE_ROLES,
                    new SystemNotification(
                        type: 'payment_reminder',
                        title: $isOverdue ? 'Geciken Ödeme' : 'Ödeme Hatırlatması',
                        message: "{$recipient} — {$payment->reference} ödemesinin plan tarihi {$scheduledDate}.",
                        actionUrl: route('finance.payments.show', $payment->id),
                        meta: [
                            'reminder_key' => $reminderKey,
                            'payment_id' => $payment->id,
                        ],
                    ),
                );

                $count += $queued;
            });

        return $count;
    }

    private function alreadySentToday(string $type, string $reminderKey): bool
    {
        return DatabaseNotification::query()
            ->where('data->type', $type)
            ->where('data->meta->reminder_key', $reminderKey)
            ->whereDate('created_at', today())
            ->exists();
    }

    private function contractShowUrl(Contract $contract): string
    {
        return match ($contract->contractable_type) {
            Agency::class => route('agencies.contracts.show', $contract->id),
            default => route('businesses.contracts.show', $contract->id),
        };
    }

    private function documentShowUrl(Document $document): string
    {
        return match ($document->documentable_type) {
            Courier::class => route('couriers.documents.show', $document->id),
            Agency::class => route('agencies.documents.show', $document->id),
            Business::class => route('businesses.documents.index'),
            default => route('businesses.documents.index'),
        };
    }
}
