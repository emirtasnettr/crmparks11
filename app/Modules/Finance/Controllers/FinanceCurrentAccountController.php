<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Core\Http\Concerns\DownloadsPdfExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\CurrentAccountFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Requests\StoreCurrentAccountMovementRequest;
use App\Modules\Finance\Requests\StoreCurrentAccountRequest;
use App\Modules\Finance\Requests\UpdateCurrentAccountRequest;
use App\Modules\Finance\Services\CurrentAccountPresenter;
use App\Modules\Finance\Services\CurrentAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceCurrentAccountController extends Controller
{
    use DownloadsListExport;
    use DownloadsPdfExport;

    public function __construct(
        private readonly CurrentAccountService $service,
        private readonly CurrentAccountPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $this->service->syncMissingEntityAccounts();

        $filters = [
            'search' => $request->string('search')->toString() ?: '',
            'type' => $request->string('type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'balance_status' => $request->string('balance_status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->service->filter($filters)->all();
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        $accountDetails = [];

        foreach ($items as $account) {
            $model = $this->service->find($account['id']);

            if ($model !== null) {
                $accountDetails[$account['id']] = $this->presenter->detailRow($model);
            }
        }

        return view('modules.finance.current-accounts.index', [
            'accounts' => $items,
            'accountDetails' => $accountDetails,
            'accountOptions' => $this->service->options(),
            'filters' => $filters,
            'accountTypes' => CurrentAccountFormData::accountTypes(),
            'statuses' => CurrentAccountFormData::statuses(),
            'balanceStatuses' => CurrentAccountFormData::balanceStatuses(),
            'transactionTypes' => CurrentAccountFormData::transactionTypes(),
            'summary' => $this->service->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function store(StoreCurrentAccountRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('finance.current-accounts.index')
            ->with('success', 'Cari hesap başarıyla oluşturuldu.');
    }

    public function update(UpdateCurrentAccountRequest $request, int $id): RedirectResponse
    {
        $this->service->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('finance.current-accounts.index')
            ->with('success', 'Cari hesap başarıyla güncellendi.');
    }

    public function storeMovement(StoreCurrentAccountMovementRequest $request): RedirectResponse
    {
        $this->service->createMovement($request->validated(), $request->user());

        return redirect()
            ->route('finance.current-accounts.index')
            ->with('success', 'Cari hareket başarıyla kaydedildi.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: '',
            'type' => $request->string('type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'balance_status' => $request->string('balance_status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'cari-hesaplar',
            FinanceListExportSheets::currentAccounts($filters),
            'Cari Hesaplar',
        );
    }

    public function statementPdf(int $id): Response
    {
        $account = $this->service->find($id);

        abort_if($account === null, 404);

        $row = $this->presenter->detailRow($account);
        $movements = $row['movements'] ?? [];

        return $this->downloadPdfTable(
            'Cari Ekstresi — '.$row['title'],
            [
                'headings' => ['Tarih', 'Belge No', 'Tür', 'Borç', 'Alacak', 'Açıklama'],
                'rows' => collect($movements)->map(fn (array $movement) => [
                    $movement['date_formatted'] ?? $movement['date'] ?? '—',
                    $movement['document_no'] ?? '—',
                    $movement['type_label'] ?? $movement['type'] ?? '—',
                    $movement['debit_formatted'] ?? '—',
                    $movement['credit_formatted'] ?? '—',
                    $movement['description'] ?? '—',
                ])->all(),
            ],
            'cari-ekstre-'.$row['code'],
            [
                'Cari Kod' => $row['code'],
                'Bakiye' => $row['balance_formatted'] ?? number_format((float) ($row['balance'] ?? 0), 2).' ₺',
            ],
        );
    }
}
