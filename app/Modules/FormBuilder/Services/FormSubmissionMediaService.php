<?php

namespace App\Modules\FormBuilder\Services;

use App\Support\PublicMediaUrl;
use App\Support\SecureUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormSubmissionMediaService
{
  private const DISK = 'public';

  private const PATH_PREFIX = 'form-submissions';

  public function store(UploadedFile $file, int $formId, int $submissionId, string $fieldName): array
  {
    $profile = SecureUploadValidator::formDocumentProfile();
    $extension = SecureUploadValidator::assertAllowed($file, $profile['extensions'], $profile['mimeTypes']);

    $safeFieldName = Str::slug($fieldName, '_') ?: 'dosya';
    $filename = $safeFieldName.'-'.Str::random(8).'.'.$extension;
    $path = $file->storeAs(self::PATH_PREFIX.'/'.$formId.'/'.$submissionId, $filename, self::DISK);

    return [
      'path' => $path,
      'url' => PublicMediaUrl::fromPath($path),
      'original_name' => basename($file->getClientOriginalName()),
    ];
  }
}
