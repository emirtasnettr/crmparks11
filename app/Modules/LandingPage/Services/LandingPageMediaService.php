<?php

namespace App\Modules\LandingPage\Services;

use App\Support\SecureUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LandingPageMediaService
{
  private const DISK = 'public';

  private const PATH_PREFIX = 'landing-pages/media';

  /**
   * @return array{path: string, url: string}
   */
  public function store(UploadedFile $file, int $pageId): array
  {
    $profile = SecureUploadValidator::imageProfile();
    $extension = SecureUploadValidator::assertAllowed($file, $profile['extensions'], $profile['mimeTypes']);

    $filename = 'hero-'.$pageId.'-'.time().'.'.$extension;
    $path = $file->storeAs(self::PATH_PREFIX.'/'.$pageId, $filename, self::DISK);

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

    return '/storage/'.ltrim($path, '/');
  }
}
