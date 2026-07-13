<?php

namespace App\Modules\ShiftPlanning\Data;

class ShiftPlanningFormData
{
    /**
     * ISO weekday: 1 = Pazartesi … 7 = Pazar
     *
     * @return array<int, string>
     */
    public static function weekDays(): array
    {
        return [
            1 => 'Pazartesi',
            2 => 'Salı',
            3 => 'Çarşamba',
            4 => 'Perşembe',
            5 => 'Cuma',
            6 => 'Cumartesi',
            7 => 'Pazar',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function weekDayShort(): array
    {
        return [
            1 => 'Pzt',
            2 => 'Sal',
            3 => 'Çar',
            4 => 'Per',
            5 => 'Cum',
            6 => 'Cmt',
            7 => 'Paz',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            '1' => 'Aktif',
            '0' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function jokerReasons(): array
    {
        return [
            'izin' => 'İzinli',
            'hasta' => 'Hasta',
            'diger' => 'Diğer',
        ];
    }
}
