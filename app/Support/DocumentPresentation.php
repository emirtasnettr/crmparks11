<?php

namespace App\Support;

final class DocumentPresentation
{
    public static function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.').' KB';
        }

        return $bytes.' B';
    }

    public static function fileTypeLabel(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'PDF',
            'doc', 'docx' => 'Word',
            'xls', 'xlsx' => 'Excel',
            'jpg', 'jpeg', 'png', 'webp' => 'Resim',
            'zip' => 'ZIP',
            default => strtoupper($extension),
        };
    }

    public static function extensionFromName(string $name): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        return $extension !== '' ? strtolower($extension) : 'bin';
    }
}
