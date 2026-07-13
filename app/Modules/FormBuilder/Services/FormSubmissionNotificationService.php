<?php

namespace App\Modules\FormBuilder\Services;

use App\Models\User;
use App\Modules\FormBuilder\Models\Form;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Notification\Services\NotificationDispatcher;
use Illuminate\Support\Collection;

class FormSubmissionNotificationService
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    /**
     * @param  array<string, mixed>  $form
     * @param  array<string, mixed>  $submission
     * @param  array<string, mixed>  $landingPage
     */
    public function notifyCreated(array $form, array $submission, array $landingPage = []): void
    {
        $recipients = $this->recipientsForForm($form);

        if ($recipients->isEmpty()) {
            return;
        }

        $formId = (int) $form['id'];
        $submissionId = (int) $submission['id'];
        $formName = (string) ($form['name'] ?? 'Form');
        $landingName = (string) ($landingPage['name'] ?? $landingPage['slug'] ?? '');

        $message = $landingName !== ''
            ? "{$formName} formuna {$landingName} üzerinden yeni başvuru geldi."
            : "{$formName} formuna yeni başvuru geldi.";

        $notification = new SystemNotification(
            type: 'form_submission_created',
            title: 'Yeni Form Başvurusu',
            message: $message,
            actionUrl: route('form-applications.show', [$formId, $submissionId]),
            meta: [
                'form_id' => $formId,
                'submission_id' => $submissionId,
                'landing_page_id' => $landingPage['id'] ?? null,
            ],
        );

        $recipients->each(function (User $user) use ($notification): void {
            $this->dispatcher->notifyUser($user, $notification);
        });
    }

    /**
     * @param  array<string, mixed>  $form
     * @return Collection<int, User>
     */
    public function recipientsForForm(array $form): Collection
    {
        $userIds = array_values(array_unique(array_map(
            'intval',
            $form['notify_user_ids'] ?? []
        )));
        $roles = array_values(array_unique(array_filter(array_map(
            'strval',
            $form['notify_roles'] ?? []
        ))));

        if ($userIds === [] && $roles === []) {
            $record = Form::query()->find((int) ($form['id'] ?? 0));
            if ($record !== null) {
                $userIds = array_values(array_map('intval', $record->notify_user_ids ?? []));
                $roles = array_values(array_map('strval', $record->notify_roles ?? []));
            }
        }

        if ($userIds === [] && $roles === []) {
            return collect();
        }

        $byId = $userIds === []
            ? collect()
            : User::query()->whereIn('id', $userIds)->get();

        $byRole = $roles === []
            ? collect()
            : User::query()->role($roles)->get();

        return $byId
            ->merge($byRole)
            ->unique('id')
            ->values();
    }
}
