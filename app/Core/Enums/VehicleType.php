<?php

namespace App\Core\Enums;

enum VehicleType: string
{
    case Motor = 'motor';
    case Car = 'car';
    case Bicycle = 'bicycle';
    case Pedestrian = 'pedestrian';

    public function label(): string
    {
        return match ($this) {
            self::Motor => 'Motor',
            self::Car => 'Otomobil',
            self::Bicycle => 'Bisiklet',
            self::Pedestrian => 'Yaya',
        };
    }
}
