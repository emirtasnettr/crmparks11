<?php

namespace App\Modules\FormBuilder\Repositories;

use App\Modules\FormBuilder\Models\Form;

class FormBuilderRepository
{
  /**
   * @return array<int, array<string, mixed>>
   */
  public function all(): array
  {
    return Form::query()
      ->orderBy('id')
      ->get()
      ->map(fn (Form $form) => $form->toRecordArray())
      ->all();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $id): ?array
  {
    $form = Form::query()->find($id);

    return $form?->toRecordArray();
  }

  /**
   * @param  array<string, mixed>  $form
   */
    public function save(array $form): void
    {
        Form::query()->updateOrCreate(
            ['id' => (int) $form['id']],
            [
                'uuid' => $form['uuid'],
                'name' => $form['name'],
                'slug' => $form['slug'],
                'description' => $form['description'] ?? '',
                'status' => $form['status'] ?? 'draft',
                'fields' => $form['fields'] ?? [],
                'notify_user_ids' => array_values(array_map('intval', $form['notify_user_ids'] ?? [])),
                'notify_roles' => array_values(array_map('strval', $form['notify_roles'] ?? [])),
                'created_at' => $form['created_at'] ?? now(),
                'updated_at' => $form['updated_at'] ?? now(),
            ],
        );
    }

  public function delete(int $id): bool
  {
    return (bool) Form::query()->whereKey($id)->delete();
  }

  public function nextId(): int
  {
    return (int) (Form::query()->max('id') ?? 0) + 1;
  }
}
