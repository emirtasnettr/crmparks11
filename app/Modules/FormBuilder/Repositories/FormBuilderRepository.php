<?php

namespace App\Modules\FormBuilder\Repositories;

use Illuminate\Support\Facades\Storage;

class FormBuilderRepository
{
  private const DISK = 'local';

  private const PATH = 'form-builder/forms.json';

  /**
   * @return array<int, array<string, mixed>>
   */
  public function all(): array
  {
    if (! Storage::disk(self::DISK)->exists(self::PATH)) {
      return [];
    }

    $decoded = json_decode(Storage::disk(self::DISK)->get(self::PATH), true);

    return is_array($decoded) ? $decoded : [];
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $id): ?array
  {
    return collect($this->all())->firstWhere('id', $id);
  }

  /**
   * @param  array<string, mixed>  $form
   */
  public function save(array $form): void
  {
    $forms = collect($this->all());
    $index = $forms->search(fn (array $item) => (int) $item['id'] === (int) $form['id']);

    if ($index === false) {
      $forms->push($form);
    } else {
      $forms[$index] = $form;
    }

    $this->write($forms->values()->all());
  }

  public function delete(int $id): bool
  {
    $forms = collect($this->all())->reject(fn (array $item) => (int) $item['id'] === $id)->values()->all();
    $deleted = count($forms) < count($this->all());
    $this->write($forms);

    return $deleted;
  }

  public function nextId(): int
  {
    $max = collect($this->all())->max('id');

    return ($max ?? 0) + 1;
  }

  /**
   * @param  array<int, array<string, mixed>>  $forms
   */
  private function write(array $forms): void
  {
    Storage::disk(self::DISK)->put(
      self::PATH,
      json_encode($forms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
  }
}
