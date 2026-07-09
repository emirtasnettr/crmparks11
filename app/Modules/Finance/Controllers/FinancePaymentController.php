<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\PaymentFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Requests\StorePaymentRequest;
use App\Modules\Finance\Services\PaymentPresenter;
use App\Modules\Finance\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinancePaymentController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly PaymentService $service,
        private readonly PaymentPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'recipient_type' => $request->string('recipient_type')->toString() ?: 'all',
            'recipient_id' => $request->string('recipient_id')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
            'payment_method' => $request->string('payment_method')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->service->filter($filters)->all();
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        $recipientsByType = $this->service->recipientsByType();

        $recipientOptions = collect($recipientsByType)
            ->when($filters['recipient_type'] !== 'all', fn ($grouped) => $grouped->only([$filters['recipient_type']]))
            ->flatMap(fn (array $items, string $type) => collect($items)->mapWithKeys(
                fn (array $item) => ["{$type}:{$item['id']}" => $item['name']]
            ))
            ->all();

        return view('modules.finance.payments.index', [
            'payments' => $items,
            'filters' => $filters,
            'recipientTypes' => PaymentFormData::recipientTypes(),
            'recipientOptions' => $recipientOptions,
            'recipientsByType' => $recipientsByType,
            'earningOptions' => $this->service->earningOptions(),
            'paymentStatuses' => PaymentFormData::paymentStatuses(),
            'paymentMethods' => PaymentFormData::paymentMethods(),
            'dateRanges' => PaymentFormData::dateRanges(),
            'summary' => $this->service->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('finance.payments.index')
            ->with('success', 'Ödeme kaydı başarıyla oluşturuldu.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'recipient_type' => $request->string('recipient_type')->toString() ?: 'all',
            'recipient_id' => $request->string('recipient_id')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
            'payment_method' => $request->string('payment_method')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'odemeler',
            FinanceListExportSheets::payments($filters),
            'Ödemeler',
        );
    }

    public function show(int $id): View
    {
        $payment = $this->service->find($id);

        abort_if($payment === null, 404);

        return view('modules.finance.payments.show', [
            'payment' => $this->presenter->showRow($payment),
        ]);
    }
}
