<?php

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Data\NotificationFormData;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function row(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $type = (string) ($data['type'] ?? 'system');
        $module = (string) ($data['module'] ?? NotificationFormData::moduleForType($type));

        return [
            'id' => $notification->id,
            'type' => $type,
            'type_label' => NotificationFormData::typeLabel($type),
            'module' => $module,
            'module_label' => NotificationFormData::moduleLabel($module),
            'title' => (string) ($data['title'] ?? 'Bildirim'),
            'message' => (string) ($data['message'] ?? ''),
            'action_url' => $data['action_url'] ?? null,
            'is_read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->toDateTimeString(),
            'read_at_formatted' => $notification->read_at?->format('d.m.Y H:i'),
            'created_at' => $notification->created_at?->toDateTimeString(),
            'created_at_formatted' => $notification->created_at?->format('d.m.Y H:i') ?? '—',
            'date_formatted' => $notification->created_at?->format('d.m.Y') ?? '—',
            'time_formatted' => $notification->created_at?->format('H:i') ?? '—',
        ];
    }
}
