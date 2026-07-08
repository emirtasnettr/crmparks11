<?php

namespace App\Modules\Business\Services;

use App\Support\PublicMediaUrl;
use App\Support\SecureUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessMediaService
{
    private const DISK = 'public';

    private const PATH_PREFIX = 'business-logos';

    /**
     * @return array{path: string, url: string}
     */
    public function storeLogo(UploadedFile $file, int $businessId): array
    {
        $profile = SecureUploadValidator::imageProfile();
        $extension = SecureUploadValidator::assertAllowed($file, $profile['extensions'], $profile['mimeTypes']);

        $filename = 'business-'.$businessId.'-'.Str::random(8).'.'.$extension;
        $path = $file->storeAs(self::PATH_PREFIX.'/'.$businessId, $filename, self::DISK);

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
