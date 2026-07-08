<?php

namespace App\Core\Enums;

enum CourierType: string
{
    case Independent = 'independent';
    case Agency = 'agency';

    public function label(): string
    {
        return match ($this) {
            self::Independent => 'Esnaf Kurye',
            self::Agency => 'Acente Kuryesi',
        };
    }
}
