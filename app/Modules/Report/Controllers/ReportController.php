<?php

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\ReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function index(): View
    {
        return view('modules.report.index');
    }

    public function radar(): View
    {
        $radar = $this->reports->radar();

        return view('modules.report.radar', [
            'workDateFormatted' => $radar['work_date_formatted'],
            'rows' => $radar['rows'],
            'summary' => $radar['summary'],
        ]);
    }
}
