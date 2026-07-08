<?php

namespace App\Modules\FormBuilder\Repositories;

use Illuminate\Support\Facades\Storage;

class FormSubmissionRepository
{
  private const DISK = 'local';

  private function path(int $formId): string
  {
    return 'form-builder/submissions/'.$formId.'.json';
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function all(int $formId): array
  {
    $path = $this->path($formId);

    if (! Storage::disk(self::DISK)->exists($path)) {
      return [];
    }

    $decoded = json_decode(Storage::disk(self::DISK)->get($path), true);

    return is_array($decoded) ? $decoded : [];
  }

  public function count(int $formId): int
  {
    return count($this->all($formId));
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $formId, int $submissionId): ?array
  {
    return collect($this->all($formId))->firstWhere('id', $submissionId);
  }

  /**
   * @param  array<string, mixed>  $submission
   */
  public function save(int $formId, array $submission): void
  {
    $submissions = collect($this->all($formId));
    $index = $submissions->search(fn (array $item) => (int) $item['id'] === (int) $submission['id']);

    if ($index === false) {
      $submissions->push($submission);
    } else {
      $submissions[$index] = $submission;
    }

    $this->write($formId, $submissions->values()->all());
  }

  public function nextId(int $formId): int
  {
    $max = collect($this->all($formId))->max('id');

    return ($max ?? 0) + 1;
  }

  /**
   * @param  array<int, array<string, mixed>>  $submissions
   */
  private function write(int $formId, array $submissions): void
  {
    Storage::disk(self::DISK)->put(
      $this->path($formId),
      json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
  }
}
