<?php

namespace App\Core\Services;

use App\Support\PublicMediaUrl;
use App\Support\SecureUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntityDocumentStorageService
{
    private const DISK = 'public';

    /**
     * @return array{original_name: string, stored_name: string, file_path: string, mime_type: string, file_size: int, disk: string}
     */
    public function store(UploadedFile $file, string $entityFolder, int $entityId): array
    {
        $profile = SecureUploadValidator::entityDocumentProfile();
        $extension = SecureUploadValidator::assertAllowed($file, $profile['extensions'], $profile['mimeTypes']);
        $storedName = Str::uuid().'.'.$extension;
        $path = $file->storeAs('documents/'.$entityFolder.'/'.$entityId, $storedName, self::DISK);

        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'file_path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'file_size' => (int) $file->getSize(),
            'disk' => self::DISK,
        ];
    }

    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return PublicMediaUrl::fromPath($path);
    }

    public function delete(?string $path, string $disk = self::DISK): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk($disk)->delete($path);
    }
}
