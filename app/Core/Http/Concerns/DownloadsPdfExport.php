<?php

namespace App\Core\Http\Concerns;

use App\Core\Exports\PdfExport;
use Illuminate\Http\Response;

trait DownloadsPdfExport
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function downloadPdf(
        string $view,
        array $data,
        string $basename,
        string $orientation = 'portrait',
    ): Response {
        return PdfExport::download($view, $data, $basename, $orientation);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function streamPdf(
        string $view,
        array $data,
        string $basename,
        string $orientation = 'portrait',
    ): Response {
        return PdfExport::stream($view, $data, $basename, $orientation);
    }

    /**
     * @param  array{headings: array<int, string>, rows: array<int, array<int, mixed>>}  $sheet
     * @param  array<string, mixed>|null  $summary
     */
    protected function downloadPdfTable(
        string $title,
        array $sheet,
        string $basename,
        ?array $summary = null,
        string $orientation = 'landscape',
    ): Response {
        return PdfExport::downloadTable($title, $sheet, $basename, $summary, $orientation);
    }
}
