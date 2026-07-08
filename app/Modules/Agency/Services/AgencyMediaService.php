<?php

namespace App\Modules\Agency\Services;

use App\Support\PublicMediaUrl;
use App\Support\SecureUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgencyMediaService
{
    private const DISK = 'public';

    private const PATH_PREFIX = 'agency-logos';

    /**
     * @return array{path: string, url: string}
     */
    public function storeLogo(UploadedFile $file, int $agencyId): array
    {
        $profile = SecureUploadValidator::imageProfile();
        $extension = SecureUploadValidator::assertAllowed($file, $profile['extensions'], $profile['mimeTypes']);

        $filename = 'agency-'.$agencyId.'-'.Str::random(8).'.'.$extension;
        $path = $file->storeAs(self::PATH_PREFIX.'/'.$agencyId, $filename, self::DISK);

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
