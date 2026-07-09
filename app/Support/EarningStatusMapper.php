<?php

namespace App\Support;

final class EarningStatusMapper
{
    public static function toStorageCode(string $uiCode): string
    {
        return $uiCode === 'pending' ? 'pending_review' : $uiCode;
    }

    public static function toUiCode(string $storageCode): string
    {
        return $storageCode === 'pending_review' ? 'pending' : $storageCode;
    }
}
