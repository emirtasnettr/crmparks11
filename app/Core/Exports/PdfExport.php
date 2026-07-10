<?php

namespace App\Core\Exports;

use App\Modules\Setting\Services\AppBrandingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class PdfExport
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function download(
        string $view,
        array $data,
        string $basename,
        string $orientation = 'portrait',
        string $paper = 'a4',
    ): Response {
        $pdf = Pdf::loadView($view, self::withBranding($data))
            ->setPaper($paper, $orientation);

        $filename = str($basename)->slug('-').'-'.now()->format('Y-m-d_His').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function stream(
        string $view,
        array $data,
        string $basename,
        string $orientation = 'portrait',
        string $paper = 'a4',
    ): Response {
        $pdf = Pdf::loadView($view, self::withBranding($data))
            ->setPaper($paper, $orientation);

        $filename = str($basename)->slug('-').'-'.now()->format('Y-m-d_His').'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * @param  array{headings: array<int, string>, rows: array<int, array<int, mixed>>}  $sheet
     * @param  array<string, mixed>|null  $summary
     */
    public static function downloadTable(
        string $title,
        array $sheet,
        string $basename,
        ?array $summary = null,
        string $orientation = 'landscape',
    ): Response {
        return self::download('exports.pdf.table', [
            'title' => $title,
            'headings' => $sheet['headings'],
            'rows' => $sheet['rows'],
            'summary' => $summary ?? [],
            'generatedAt' => now()->format('d.m.Y H:i'),
        ], $basename, $orientation);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function withBranding(array $data): array
    {
        $branding = app(AppBrandingService::class)->resolve();

        return array_merge([
            'systemName' => $branding['system_name'] ?? config('crmlog.name', 'CRMLog'),
            'generatedAt' => now()->format('d.m.Y H:i'),
        ], $data);
    }
}
