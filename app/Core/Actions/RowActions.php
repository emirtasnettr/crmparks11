<?php

namespace App\Core\Actions;

class RowActions
{
    public static function link(string $label, string $href, string $tone = 'default'): array
    {
        return [
            'type' => 'link',
            'label' => $label,
            'href' => $href,
            'tone' => $tone,
        ];
    }

  /**
   * @param  array<string, mixed>|int|string|null  $payload
   */
    public static function dispatch(string $label, string $event, mixed $payload = [], string $tone = 'default'): array
    {
        return [
            'type' => 'dispatch',
            'label' => $label,
            'dispatch' => $event,
            'payload' => $payload,
            'tone' => $tone,
        ];
    }

    public static function run(
        string $label,
        string $action,
        ?string $message = null,
        ?string $confirm = null,
        string $tone = 'default',
        mixed $id = null,
        ?string $modal = null,
    ): array {
        return array_filter([
            'type' => 'action',
            'label' => $label,
            'action' => $action,
            'message' => $message,
            'confirm' => $confirm,
            'tone' => $tone,
            'id' => $id,
            'modal' => $modal,
        ], static fn ($value) => $value !== null);
    }

    public static function divider(): array
    {
        return ['type' => 'divider'];
    }

    public static function modal(string $label, string $event, string $modal = 'create', mixed $id = null, string $tone = 'default'): array
    {
        return self::dispatch($label, $event, ['modal' => $modal, 'id' => $id], $tone);
    }
}
