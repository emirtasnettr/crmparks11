<?php

namespace App\Core\Exports;

use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ListExport
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public static function download(string $basename, array $headings, array $rows, ?string $sheetTitle = null): BinaryFileResponse
    {
        $filename = str($basename)->slug('-').'-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(
            new TabularExport($headings, $rows, $sheetTitle ?? str($basename)->limit(31)->toString()),
            $filename
        );
    }

    /**
     * @param  array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>}>  $sheets
     */
    public static function downloadMultiple(string $basename, array $sheets): BinaryFileResponse
    {
        $filename = str($basename)->slug('-').'-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new MultiSheetExport($sheets), $filename);
    }

    public static function yesNo(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Evet' : 'Hayır';
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $headings
     * @param  array<int, callable(array<string, mixed>): array<int, mixed>>  $columns
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function sheet(array $items, array $headings, array $columns): array
    {
        $rows = collect($items)
            ->map(fn (array $item) => collect($columns)->map(fn (callable $column) => $column($item))->all())
            ->values()
            ->all();

        return [
            'headings' => $headings,
            'rows' => $rows,
        ];
    }
}
