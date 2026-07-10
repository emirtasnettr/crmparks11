<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyContactFormData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Requests\StoreAgencyContactRequest;
use App\Modules\Agency\Services\AgencyContactPresenter;
use App\Modules\Agency\Services\AgencyContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyContactController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly AgencyContactService $contacts,
        private readonly AgencyContactPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'title' => $request->string('title')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->contacts->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($contact) => $this->presenter->indexRow($contact))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.contacts.index', [
            'contacts' => $items,
            'filters' => $filters,
            'agencies' => $this->contacts->agencies(),
            'titles' => AgencyContactFormData::titles(),
            'summary' => $this->contacts->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'title' => $request->string('title')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-yetkilileri',
            AgencyListExportSheets::contacts($filters),
            'Acente Yetkilileri',
        );
    }

    public function store(StoreAgencyContactRequest $request): RedirectResponse
    {
        $contact = $this->contacts->create($request->validated());

        if ($request->boolean('redirect_to_agency')) {
            return redirect()
                ->route('agencies.show', $contact->agency_id)
                ->with('success', 'Yetkili başarıyla eklendi.');
        }

        return redirect()
            ->route('agencies.contacts.index', ['agency_id' => $contact->agency_id])
            ->with('success', 'Yetkili başarıyla eklendi.');
    }

    public function show(int $id): View
    {
        $contact = $this->contacts->find($id);

        abort_if($contact === null, 404);

        return view('modules.agency.contacts.show', [
            'contact' => $this->presenter->showRow($contact),
        ]);
    }

    public function deactivate(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('agency.update'), 403);

        $contact = $this->contacts->find($id);
        abort_if($contact === null, 404);

        $this->contacts->deactivate($contact);

        return redirect()
            ->route('agencies.contacts.index', ['agency_id' => $contact->agency_id])
            ->with('success', 'Yetkili pasife alındı.');
    }
}
