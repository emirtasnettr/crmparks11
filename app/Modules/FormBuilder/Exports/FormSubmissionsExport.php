<?php

namespace App\Modules\FormBuilder\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FormSubmissionsExport implements FromCollection, ShouldAutoSize, WithHeadings
{
  /**
   * @param  array<string, mixed>  $form
   * @param  array<int, array<string, mixed>>  $submissions
   * @param  array<int, array{name: string, label: string, type: string}>  $exportableFields
   */
  public function __construct(
    private readonly array $form,
    private readonly array $submissions,
    private readonly array $exportableFields,
  ) {}

  public function headings(): array
  {
    return array_merge(
      ['ID', 'Gönderim Tarihi', 'Landing Page'],
      collect($this->exportableFields)->pluck('label')->all()
    );
  }

  public function collection(): Collection
  {
    return collect($this->submissions)->map(function (array $submission) {
      $row = [
        $submission['id'],
        $submission['submitted_at_formatted'] ?? $submission['submitted_at'] ?? '',
        $submission['landing_page_name'] ?? $submission['landing_page_slug'] ?? '',
      ];

      foreach ($this->exportableFields as $field) {
        $value = $submission['data'][$field['name']] ?? '';

        if (($field['type'] ?? '') === 'file' && ! empty($submission['data'][$field['name'].'_url'])) {
          $value = $submission['data'][$field['name'].'_url'];
        }

        $row[] = is_array($value) ? implode(', ', $value) : (string) $value;
      }

      return $row;
    });
  }
}
