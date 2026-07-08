<?php

namespace App\Core\Enums;

enum UserType: string
{
    case Internal = 'internal';
    case Courier = 'courier';
    case Business = 'business';
    case Agency = 'agency';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Dahili Kullanıcı',
            self::Courier => 'Kurye',
            self::Business => 'İşletme',
            self::Agency => 'Acente',
        };
    }
}
