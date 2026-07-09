<?php

namespace App\Modules\Business\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessDocumentFormData;
use App\Modules\Business\Requests\StoreBusinessDocumentRequest;
use App\Modules\Business\Services\BusinessDocumentPresenter;
use App\Modules\Business\Services\BusinessDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessDocumentController extends Controller
{
    public function __construct(
        private readonly BusinessDocumentService $documents,
        private readonly BusinessDocumentPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'document_type' => $request->string('document_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->documents->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($document) => $this->presenter->indexRow($document))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.documents.index', [
            'documents' => $items,
            'filters' => $filters,
            'businesses' => $this->documents->businesses(),
            'documentTypes' => BusinessDocumentFormData::documentTypes(),
            'statuses' => BusinessDocumentFormData::statuses(),
            'dateRanges' => BusinessDocumentFormData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function store(StoreBusinessDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->create(
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        if ($request->boolean('redirect_to_business')) {
            return redirect()
                ->route('businesses.show', $document->documentable_id)
                ->with('success', 'Evrak başarıyla yüklendi.');
        }

        return redirect()
            ->route('businesses.documents.index', ['business_id' => $document->documentable_id])
            ->with('success', 'Evrak başarıyla yüklendi.');
    }
}
