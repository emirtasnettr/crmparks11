<?php

namespace App\Modules\Courier\Services;

use App\Support\PublicMediaUrl;
use App\Support\SecureUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourierMediaService
{
    private const DISK = 'public';

    private const PATH_PREFIX = 'courier-photos';

    /**
     * @return array{path: string, url: string}
     */
    public function storePhoto(UploadedFile $file, int $courierId): array
    {
        $profile = SecureUploadValidator::imageProfile();
        $extension = SecureUploadValidator::assertAllowed($file, $profile['extensions'], $profile['mimeTypes']);

        $filename = 'courier-'.$courierId.'-'.Str::random(8).'.'.$extension;
        $path = $file->storeAs(self::PATH_PREFIX.'/'.$courierId, $filename, self::DISK);

        return [
            'path' => $path,
            'url' => $this->url($path),
        ];
    }

    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return PublicMediaUrl::fromPath($path);
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }
}
