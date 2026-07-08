<?php

namespace App\Core\Enums;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case PendingDocuments = 'pending_documents';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Pasif',
            self::Suspended => 'Askıya Alındı',
            self::PendingDocuments => 'Belge Bekliyor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'secondary',
            self::Suspended => 'danger',
            self::PendingDocuments => 'warning',
        };
    }
}
