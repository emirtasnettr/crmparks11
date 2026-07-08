<?php

namespace App\Core\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>}>  $sheets
     */
    public function __construct(private readonly array $sheets) {}

    public function sheets(): array
    {
        return collect($this->sheets)
            ->map(fn (array $sheet) => new TabularExport(
                $sheet['headings'],
                $sheet['rows'],
                $sheet['title'],
            ))
            ->all();
    }
}
