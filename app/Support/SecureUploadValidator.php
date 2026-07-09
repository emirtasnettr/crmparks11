<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class SecureUploadValidator
{
    /**
     * @param  array<int, string>  $extensions
     * @param  array<int, string>  $mimeTypes
     */
    public static function assertAllowed(UploadedFile $file, array $extensions, array $mimeTypes): string
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Dosya yüklenemedi.');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        if ($extension === '' || ! in_array($extension, $extensions, true)) {
            throw new InvalidArgumentException('Desteklenmeyen dosya formatı.');
        }

        $detectedMime = strtolower((string) $file->getMimeType());

        if ($detectedMime === '' || ! in_array($detectedMime, $mimeTypes, true)) {
            throw new InvalidArgumentException('Geçersiz dosya türü.');
        }

        $guessedExtension = strtolower((string) $file->guessExtension());

        if ($guessedExtension !== '' && ! in_array($guessedExtension, $extensions, true)) {
            throw new InvalidArgumentException('Dosya içeriği uzantı ile uyuşmuyor.');
        }

        return $extension;
    }

    /**
     * @return array{extensions: array<int, string>, mimeTypes: array<int, string>}
     */
    public static function imageProfile(): array
    {
        return [
            'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
            'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
        ];
    }

    /**
     * @return array{extensions: array<int, string>, mimeTypes: array<int, string>}
     */
    public static function formDocumentProfile(): array
    {
        return [
            'extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'webp'],
            'mimeTypes' => [
                'application/pdf',
                'image/png',
                'image/jpeg',
                'image/webp',
            ],
        ];
    }

    /**
     * @return array{extensions: array<int, string>, mimeTypes: array<int, string>}
     */
    public static function entityDocumentProfile(): array
    {
        return [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
            'mimeTypes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/zip',
                'application/x-zip-compressed',
            ],
        ];
    }
}
