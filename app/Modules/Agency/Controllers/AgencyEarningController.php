<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Core\Http\Concerns\DownloadsPdfExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyEarningFormData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Services\AgencyEarningService;
use App\Modules\Agency\Support\AgencyFeatures;
use App\Modules\Business\Requests\ImportBusinessEarningRequest;
use App\Modules\Business\Services\BusinessEarningImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyEarningController extends Controller
{
    use DownloadsListExport;
    use DownloadsPdfExport;

    public function __construct(
        private readonly AgencyEarningService $earnings,
        private readonly BusinessEarningImportService $importer,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->earnings->filter($filters);
        $total = $all->count();
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values()->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.earnings.index', [
            'earnings' => $items,
            'filters' => $filters,
            'agencies' => $this->earnings->agencies(),
            'months' => AgencyEarningFormData::months(),
            'earningStatuses' => AgencyEarningFormData::earningStatuses(),
            'paymentStatuses' => AgencyEarningFormData::paymentStatuses(),
            'summary' => $this->earnings->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-hakedisleri',
            AgencyListExportSheets::earnings($filters),
            'Acente Hakedişleri',
        );
    }

    public function template(): BinaryFileResponse|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        abort_unless(auth()->user()?->can('earning.create'), 403);

        return $this->downloadExportSheet(
            'hakedis-sablonu',
            $this->importer->templateSheet(),
            'Hakediş Şablonu',
        );
    }

    public function import(ImportBusinessEarningRequest $request): RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            abort(404);
        }

        $result = $this->importer->import($request->file('file'), $request->user());

        $message = "{$result['imported']} hakediş içe aktarıldı.";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} satır atlandı.";
        }

        return redirect()
            ->route('agencies.earnings.index')
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function show(int $id): View|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        $earning = $this->earnings->find($id);

        abort_if($earning === null, 404);

        return view('modules.agency.earnings.show', [
            'earning' => $earning,
        ]);
    }

    public function pdf(int $id): Response|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        $row = $this->earnings->find($id);

        abort_if($row === null, 404);

        $paymentStatusLabel = AgencyEarningFormData::paymentStatuses()[$row['payment_status']] ?? $row['payment_status'];
        $statusLabel = AgencyEarningFormData::earningStatuses()[$row['status']] ?? $row['status'];

        return $this->streamPdf('exports.pdf.document', [
            'title' => 'Hakediş '.$row['reference'],
            'subtitle' => $row['period_label'],
            'fields' => [
                'Hakediş No' => $row['reference'],
                'Acente' => $row['agency_name'],
                'Şehir' => $row['agency_city'],
                'Telefon' => $row['agency_phone'],
                'Dönem' => $row['period_label'],
                'Dönem Tipi' => $row['period_type_label'],
                'Kurye Sayısı' => $row['courier_count'],
                'Paket Sayısı' => $row['package_count'],
                'Ödeme Durumu' => $paymentStatusLabel,
                'Durum' => $statusLabel,
                'Ödeme Tarihi' => $row['payment_date_formatted'],
            ],
            'totals' => [
                'Brüt Hakediş' => money_excl_vat($row['gross_amount']),
                'Kesinti' => money_excl_vat($row['deduction']),
                'Net Ödeme' => money_excl_vat($row['net_payment']),
            ],
        ], 'acente-hakedis-'.$row['reference']);
    }
}
