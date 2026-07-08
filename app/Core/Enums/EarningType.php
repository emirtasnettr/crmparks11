<?php

namespace App\Core\Enums;

enum EarningType: string
{
    case PackageBased = 'package_based';
    case FixedPeriod = 'fixed_period';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::PackageBased => 'Paket Bazlı',
            self::FixedPeriod => 'Sabit Dönemsel',
            self::Custom => 'Özel Fiyatlandırma',
        };
    }
}
