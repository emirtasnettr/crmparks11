<?php

namespace App\Core\Enums;

enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Açık',
            self::Dark => 'Koyu',
            self::System => 'Sistem',
        };
    }
}
