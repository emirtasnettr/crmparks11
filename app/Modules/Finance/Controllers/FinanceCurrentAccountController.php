<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Core\Http\Concerns\DownloadsPdfExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\CurrentAccountFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Requests\StoreCurrentAccountMovementRequest;
use App\Modules\Finance\Requests\StoreCurrentAccountRequest;
use App\Modules\Finance\Requests\UpdateCurrentAccountRequest;
use App\Modules\Finance\Services\CurrentAccountPresenter;
use App\Modules\Finance\Services\CurrentAccountService;
use App\Modules\Finance\Services\PaymentService;
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
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('finance.current-accounts.business', $request->query());
    }

    public function business(Request $request): View
    {
        return $this->indexForType($request, 'business');
    }

    public function courier(Request $request): View
    {
        return $this->indexForType($request, 'courier');
    }

    public function store(StoreCurrentAccountRequest $request): RedirectResponse
    {
        $account = $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route($this->indexRouteName($account->account_type))
            ->with('success', 'Cari hesap başarıyla oluşturuldu.');
    }

    public function update(UpdateCurrentAccountRequest $request, int $id): RedirectResponse
    {
        $account = $this->service->update($id, $request->validated(), $request->user());

        return redirect()
            ->route($this->indexRouteName($account->account_type))
            ->with('success', 'Cari hesap başarıyla güncellendi.');
    }

    public function deactivate(Request $request, int $id): RedirectResponse
    {
        $account = $this->service->deactivate($id, $request->user());

        return redirect()
            ->route($this->indexRouteName($account->account_type))
            ->with('success', 'Cari hesap pasife alındı.');
    }

    public function storeMovement(StoreCurrentAccountMovementRequest $request): RedirectResponse
    {
        $movement = $this->service->createMovement($request->validated(), $request->user());
        $account = CurrentAccount::query()->find($movement->current_account_id);

        return redirect()
            ->route($this->indexRouteName($account?->account_type))
            ->with('success', 'Cari hareket başarıyla kaydedildi.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $type = $this->resolveExportType($request);
        $filters = [
            'search' => $request->string('search')->toString() ?: '',
            'type' => $type,
            'status' => $request->string('status')->toString() ?: 'all',
            'balance_status' => $request->string('balance_status')->toString() ?: 'all',
        ];

        $label = $type === 'courier' ? 'Kurye Cari' : 'İşletme Cari';

        return $this->downloadExportSheet(
            $type === 'courier' ? 'kurye-cari' : 'isletme-cari',
            FinanceListExportSheets::currentAccounts($filters),
            $label,
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

    private function indexForType(Request $request, string $type): View
    {
        $this->service->syncMissingEntityAccounts();
        $this->payments->backfillEarningLiabilities($request->user());

        $filters = [
            'search' => $request->string('search')->toString() ?: '',
            'type' => $type,
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

        $isCourier = $type === 'courier';

        return view('modules.finance.current-accounts.index', [
            'accounts' => $items,
            'accountDetails' => $accountDetails,
            'accountOptions' => $this->service->options($type),
            'accountScope' => $type,
            'filters' => $filters,
            'accountTypes' => CurrentAccountFormData::accountTypes(),
            'statuses' => CurrentAccountFormData::statuses(),
            'balanceStatuses' => CurrentAccountFormData::balanceStatuses(),
            'transactionTypes' => $isCourier
                ? CurrentAccountFormData::courierTransactionTypes()
                : CurrentAccountFormData::businessTransactionTypes(),
            'primaryMovementType' => $isCourier ? 'payment' : 'collection',
            'primaryMovementLabel' => $isCourier ? 'Ödeme Yapıldı' : 'Ödeme Alındı',
            'pageTitle' => $isCourier ? 'Kurye Cari' : 'İşletme Cari',
            'pageSubtitle' => $isCourier
                ? 'Kuryelere olan borçlarınızı ve yaptığınız ödemeleri takip edin.'
                : 'İşletmelerden alacaklarınızı ve aldığınız ödemeleri takip edin.',
            'indexRoute' => $isCourier
                ? 'finance.current-accounts.courier'
                : 'finance.current-accounts.business',
            'summary' => $this->service->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    private function indexRouteName(?string $accountType): string
    {
        return $accountType === 'courier'
            ? 'finance.current-accounts.courier'
            : 'finance.current-accounts.business';
    }

    private function resolveExportType(Request $request): string
    {
        $type = $request->string('type')->toString();

        if (in_array($type, ['business', 'courier'], true)) {
            return $type;
        }

        return str_contains((string) $request->headers->get('referer'), '/cari/kurye')
            ? 'courier'
            : 'business';
    }
}
