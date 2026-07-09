<?php

namespace App\Modules\Agency\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyDocumentFormData;
use App\Modules\Agency\Requests\StoreAgencyDocumentRequest;
use App\Modules\Agency\Services\AgencyDocumentPresenter;
use App\Modules\Agency\Services\AgencyDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgencyDocumentController extends Controller
{
    public function __construct(
        private readonly AgencyDocumentService $documents,
        private readonly AgencyDocumentPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'document_type' => $request->string('document_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'expiry_filter' => $request->string('expiry_filter')->toString() ?: 'all',
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

        return view('modules.agency.documents.index', [
            'documents' => $items,
            'filters' => $filters,
            'agencies' => $this->documents->agencies(),
            'documentTypes' => AgencyDocumentFormData::documentTypes(),
            'statuses' => AgencyDocumentFormData::statuses(),
            'expiryFilters' => AgencyDocumentFormData::expiryFilters(),
            'summary' => $this->documents->summary($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $document = $this->documents->find($id);

        abort_if($document === null, 404);

        return view('modules.agency.documents.show', [
            'document' => $this->presenter->showRow($document),
        ]);
    }

    public function store(StoreAgencyDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->create(
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        if ($request->boolean('redirect_to_agency')) {
            return redirect()
                ->route('agencies.show', $document->documentable_id)
                ->with('success', 'Evrak başarıyla yüklendi.');
        }

        return redirect()
            ->route('agencies.documents.index', ['agency_id' => $document->documentable_id])
            ->with('success', 'Evrak başarıyla yüklendi.');
    }
}
