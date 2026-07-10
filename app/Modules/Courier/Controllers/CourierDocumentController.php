<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierDocumentFormData;
use App\Modules\Courier\Requests\StoreCourierDocumentRequest;
use App\Modules\Courier\Services\CourierDocumentPresenter;
use App\Modules\Courier\Services\CourierDocumentService;
use App\Support\EntityCardRedirect;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierDocumentController extends Controller
{
    public function __construct(
        private readonly CourierDocumentService $documents,
        private readonly CourierDocumentPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'courier_id' => RequestFilter::valueOrAll($request, 'courier_id'),
            'document_type' => RequestFilter::valueOrAll($request, 'document_type'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
            'expiry_filter' => RequestFilter::valueOrAll($request, 'expiry_filter'),
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

        return view('modules.courier.documents.index', [
            'documents' => $items,
            'filters' => $filters,
            'couriers' => $this->documents->couriers(),
            'documentTypes' => CourierDocumentFormData::documentTypes(),
            'statuses' => CourierDocumentFormData::statuses(),
            'expiryFilters' => CourierDocumentFormData::expiryFilters(),
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

        return view('modules.courier.documents.show', [
            'document' => $this->presenter->showRow($document),
        ]);
    }

    public function store(StoreCourierDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->create(
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        if ($request->boolean('redirect_to_courier')) {
            return EntityCardRedirect::toShow(
                route('couriers.show', $document->documentable_id),
                'documents',
                'Belge başarıyla yüklendi.',
            );
        }

        return redirect()
            ->route('couriers.documents.index', ['courier_id' => $document->documentable_id])
            ->with('success', 'Belge başarıyla yüklendi.');
    }

    public function download(Request $request, int $id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()?->can('courier.view'), 403);

        $document = $this->documents->find($id);
        abort_if($document === null, 404);

        return \Illuminate\Support\Facades\Storage::disk($document->disk ?: 'public')
            ->download($document->file_path, $document->original_name);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('courier.update'), 403);

        $document = $this->documents->find($id);
        abort_if($document === null, 404);

        $courierId = $document->documentable_id;
        $this->documents->destroy($document);

        return EntityCardRedirect::after(
            route('couriers.documents.index', ['courier_id' => $courierId]),
            'Belge silindi.',
            route('couriers.show', $courierId),
            'documents',
        );
    }
}
