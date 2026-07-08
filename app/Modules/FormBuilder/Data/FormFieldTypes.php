<?php

namespace App\Modules\FormBuilder\Data;

class FormFieldTypes
{
  /**
   * @return array<string, array{label: string, icon: string, category: string}>
   */
  public static function palette(): array
  {
    return [
      'text' => ['label' => 'Metin', 'icon' => 'text', 'category' => 'basic'],
      'textarea' => ['label' => 'Uzun Metin', 'icon' => 'textarea', 'category' => 'basic'],
      'email' => ['label' => 'E-posta', 'icon' => 'email', 'category' => 'basic'],
      'phone' => ['label' => 'Telefon', 'icon' => 'phone', 'category' => 'basic'],
      'number' => ['label' => 'Sayı', 'icon' => 'number', 'category' => 'basic'],
      'date' => ['label' => 'Tarih', 'icon' => 'date', 'category' => 'basic'],
      'select' => ['label' => 'Seçim Listesi', 'icon' => 'select', 'category' => 'choice'],
      'radio' => ['label' => 'Tek Seçim', 'icon' => 'radio', 'category' => 'choice'],
      'checkbox' => ['label' => 'Onay Kutusu', 'icon' => 'checkbox', 'category' => 'choice'],
      'file' => ['label' => 'Dosya', 'icon' => 'file', 'category' => 'advanced'],
      'heading' => ['label' => 'Bölüm Başlığı', 'icon' => 'heading', 'category' => 'layout'],
    ];
  }

  /**
   * @return array<string, string>
   */
  public static function labels(): array
  {
    return collect(self::palette())->mapWithKeys(fn (array $item, string $key) => [$key => $item['label']])->all();
  }

  /**
   * @return array<string, mixed>
   */
  public static function defaultField(string $type, int $index = 1): array
  {
    $palette = self::palette()[$type] ?? self::palette()['text'];
    $baseName = str_replace('-', '_', $type).'_'.$index;

    $field = [
      'id' => 'field_'.bin2hex(random_bytes(4)),
      'type' => $type,
      'label' => $palette['label'],
      'name' => $baseName,
      'placeholder' => '',
      'help_text' => '',
      'required' => ! in_array($type, ['heading', 'checkbox'], true),
      'width' => 'full',
      'options' => [],
    ];

    if ($type === 'heading') {
      $field['required'] = false;
      $field['name'] = 'heading_'.$index;
      $field['placeholder'] = 'Bölüm açıklaması (isteğe bağlı)';
    }

    if (in_array($type, ['select', 'radio'], true)) {
      $field['options'] = ['Seçenek 1', 'Seçenek 2', 'Seçenek 3'];
    }

    if ($type === 'checkbox') {
      $field['label'] = 'Onaylıyorum';
      $field['required'] = false;
    }

    return $field;
  }
}
