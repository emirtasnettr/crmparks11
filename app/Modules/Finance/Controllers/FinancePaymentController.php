<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinancePaymentDummyData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinancePaymentController extends Controller
{
    use DownloadsListExport;
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

        $all = FinancePaymentDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        $recipientOptions = collect(FinancePaymentDummyData::recipientsByType())
            ->when($filters['recipient_type'] !== 'all', fn ($grouped) => $grouped->only([$filters['recipient_type']]))
            ->flatMap(fn (array $items, string $type) => collect($items)->mapWithKeys(
                fn (array $item) => ["{$type}:{$item['id']}" => $item['name']]
            ))
            ->all();

        return view('modules.finance.payments.index', [
            'payments' => $items,
            'filters' => $filters,
            'recipientTypes' => FinancePaymentDummyData::recipientTypes(),
            'recipientOptions' => $recipientOptions,
            'recipientsByType' => FinancePaymentDummyData::recipientsByType(),
            'earningOptions' => FinancePaymentDummyData::earningOptions(),
            'paymentStatuses' => FinancePaymentDummyData::paymentStatuses(),
            'paymentMethods' => FinancePaymentDummyData::paymentMethods(),
            'dateRanges' => FinancePaymentDummyData::dateRanges(),
            'summary' => FinancePaymentDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
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
        $payment = FinancePaymentDummyData::find($id);

        abort_if($payment === null, 404);

        return view('modules.finance.payments.show', [
            'payment' => $payment,
        ]);
    }
}
