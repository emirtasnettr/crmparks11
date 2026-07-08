<?php

namespace App\Core\Http\Concerns;

use App\Core\Exports\ListExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait DownloadsListExport
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function downloadListExport(string $basename, array $headings, array $rows, ?string $sheetTitle = null): BinaryFileResponse
    {
        return ListExport::download($basename, $headings, $rows, $sheetTitle);
    }

    /**
     * @param  array{headings: array<int, string>, rows: array<int, array<int, mixed>>}  $sheet
     */
    protected function downloadExportSheet(string $basename, array $sheet, ?string $sheetTitle = null): BinaryFileResponse
    {
        return $this->downloadListExport($basename, $sheet['headings'], $sheet['rows'], $sheetTitle);
    }

    /**
     * @param  array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>}>  $sheets
     */
    protected function downloadMultipleExportSheets(string $basename, array $sheets): BinaryFileResponse
    {
        return ListExport::downloadMultiple($basename, $sheets);
    }
}
