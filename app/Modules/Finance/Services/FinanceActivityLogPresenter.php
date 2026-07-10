<?php

namespace App\Modules\Finance\Services;

use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Finance\Data\FinanceActivityLogFormData;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;

class FinanceActivityLogPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(ActivityLog $log): array
    {
        return $this->enrich($log);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(ActivityLog $log): array
    {
        $row = $this->enrich($log);

        return [
            'action_type_label' => $row['action_type_label'],
            'module_label' => $row['module_label'],
            'occurred_at' => $row['date_formatted'].' '.$row['time_formatted'],
            'user_name' => $row['user_name'],
            'ip_address' => $row['ip_address'],
            'browser' => $row['browser'],
            'operating_system' => $row['operating_system'],
            'old_values_json' => $row['old_values_json'],
            'new_values_json' => $row['new_values_json'],
            'description' => $row['description'],
            'related_route' => $row['related_route'],
        ];
    }

    public function resolveCurrentAccountName(ActivityLog $log): string
    {
        $log->loadMissing(['subject']);
        $this->loadSubjectRelations($log);

        $subject = $log->subject;

        return match (true) {
            $subject instanceof FinanceRevenue => $subject->business?->displayName()
                ?? $subject->currentAccount?->title
                ?? '—',
            $subject instanceof FinanceExpense => $subject->courier?->full_name
                ?? $subject->agency?->displayName()
                ?? '—',
            $subject instanceof FinanceCollection => $subject->business?->displayName() ?? '—',
            $subject instanceof FinancePayment => $subject->recipient_name ?? '—',
            $subject instanceof FinanceInvoice => $subject->business?->displayName() ?? '—',
            $subject instanceof CurrentAccount => $subject->title ?? '—',
            $subject instanceof CurrentAccountMovement => $subject->currentAccount?->title ?? '—',
            default => '—',
        };
    }

    public function resolveReference(ActivityLog $log): string
    {
        $log->loadMissing(['subject']);
        $this->loadSubjectRelations($log);

        if ($log->subject && method_exists($log->subject, 'getAttribute')) {
            $reference = $log->subject->getAttribute('reference');

            if (is_string($reference) && $reference !== '') {
                return $reference;
            }
        }

        return (string) ($log->new_values['reference'] ?? $log->description ?? '—');
    }

    public function resolveCurrentAccountCode(ActivityLog $log): ?string
    {
        $log->loadMissing(['subject']);

        $subject = $log->subject;

        if ($subject instanceof CurrentAccount) {
            return $subject->code;
        }

        if ($subject instanceof CurrentAccountMovement) {
            return $subject->currentAccount?->code;
        }

        if ($subject instanceof FinanceRevenue || $subject instanceof FinanceCollection) {
            return $subject->currentAccount?->code;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(ActivityLog $log): array
    {
        $log->loadMissing(['user', 'subject']);
        $this->loadSubjectRelations($log);

        $occurredAt = $log->created_at ?? now();
        $module = FinanceActivityLogFormData::moduleForSubjectType($log->subject_type) ?? 'revenues';
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];
        $status = $this->resolveStatus($log);
        [$browser, $operatingSystem] = $this->parseUserAgent($log->user_agent);

        return [
            'id' => $log->id,
            'log_name' => 'finance',
            'module' => $module,
            'action_type' => $log->action,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'reference' => $this->resolveReference($log),
            'current_account_name' => $this->resolveCurrentAccountName($log),
            'current_account_code' => $this->resolveCurrentAccountCode($log),
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'user_id' => $log->user_id,
            'user_name' => $log->user?->name ?? '—',
            'ip_address' => $log->ip_address ?? '—',
            'user_agent' => $log->user_agent ?? '—',
            'browser' => $browser,
            'operating_system' => $operatingSystem,
            'status' => $status,
            'is_critical' => $this->isCritical($log, $status),
            'description' => $log->description ?? '—',
            'properties' => [
                'old' => $oldValues,
                'attributes' => $newValues,
            ],
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'module_label' => FinanceActivityLogFormData::modules()[$module] ?? $module,
            'action_type_label' => FinanceActivityLogFormData::actionTypes()[$log->action] ?? $log->action,
            'status_label' => FinanceActivityLogFormData::statuses()[$status] ?? $status,
            'date_formatted' => $occurredAt->format('d.m.Y'),
            'time_formatted' => $occurredAt->format('H:i:s'),
            'related_route' => $this->relatedRoute($module, (int) $log->subject_id),
            'old_values_json' => json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}',
            'new_values_json' => json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}',
        ];
    }

    private function resolveStatus(ActivityLog $log): string
    {
        $newStatus = $log->new_values['status'] ?? null;

        return match (true) {
            in_array($newStatus, ['cancelled', 'deleted', 'overdue'], true) => 'warning',
            ($log->new_values['error'] ?? false) === true => 'error',
            default => 'success',
        };
    }

    private function isCritical(ActivityLog $log, string $status): bool
    {
        return $status === 'error'
            || in_array($log->new_values['status'] ?? null, ['cancelled', 'deleted'], true);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (! $userAgent) {
            return ['—', '—'];
        }

        $browser = 'Bilinmiyor';
        $os = 'Bilinmiyor';

        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        } elseif ($userAgent === 'PHPUnit') {
            $browser = 'PHPUnit';
        }

        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif ($userAgent === 'PHPUnit') {
            $os = 'Test';
        }

        return [$browser, $os];
    }

    private function relatedRoute(string $module, int $subjectId): ?string
    {
        if ($subjectId <= 0) {
            return null;
        }

        return match ($module) {
            'revenues' => route('finance.revenues.show', $subjectId),
            'expenses' => route('finance.expenses.show', $subjectId),
            'collections' => route('finance.collections.show', $subjectId),
            'payments' => route('finance.payments.show', $subjectId),
            'invoices' => route('finance.invoices.show', $subjectId),
            'current_accounts' => route('finance.current-accounts.index'),
            default => null,
        };
    }

    private function loadSubjectRelations(ActivityLog $log): void
    {
        $subject = $log->subject;

        if ($subject instanceof FinanceRevenue) {
            $subject->loadMissing(['business', 'currentAccount']);

            return;
        }

        if ($subject instanceof FinanceExpense) {
            $subject->loadMissing(['courier', 'agency']);

            return;
        }

        if ($subject instanceof FinanceCollection || $subject instanceof FinanceInvoice) {
            $subject->loadMissing(['business']);

            return;
        }

        if ($subject instanceof CurrentAccountMovement) {
            $subject->loadMissing(['currentAccount']);
        }
    }
}
