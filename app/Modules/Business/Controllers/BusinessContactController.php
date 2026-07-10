<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessContactFormData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Requests\StoreBusinessContactRequest;
use App\Modules\Business\Requests\UpdateBusinessContactRequest;
use App\Modules\Business\Services\BusinessContactPresenter;
use App\Modules\Business\Services\BusinessContactService;
use App\Support\EntityCardRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessContactController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly BusinessContactService $contacts,
        private readonly BusinessContactPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
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

        return view('modules.business.contacts.index', [
            'contacts' => $items,
            'filters' => $filters,
            'businesses' => $this->contacts->businesses(),
            'titles' => BusinessContactFormData::titles(),
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
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'title' => $request->string('title')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'isletme-yetkilileri',
            BusinessListExportSheets::contacts($filters),
            'İşletme Yetkilileri',
        );
    }

    public function store(StoreBusinessContactRequest $request): RedirectResponse
    {
        $contact = $this->contacts->create($request->validated());

        if ($request->boolean('redirect_to_business')) {
            return EntityCardRedirect::toShow(
                route('businesses.show', $contact->business_id),
                'contacts',
                'Yetkili başarıyla eklendi.',
            );
        }

        return redirect()
            ->route('businesses.contacts.index', ['business_id' => $contact->business_id])
            ->with('success', 'Yetkili başarıyla eklendi.');
    }

    public function update(UpdateBusinessContactRequest $request, int $id): RedirectResponse
    {
        $contact = $this->contacts->find($id);

        if ($contact === null) {
            abort(404);
        }

        $this->contacts->update($contact, $request->validated());

        return redirect()
            ->back()
            ->with('success', 'Yetkili bilgileri güncellendi.');
    }

    public function deactivate(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('business.update'), 403);

        $contact = $this->contacts->find($id);
        abort_if($contact === null, 404);

        $this->contacts->deactivate($contact);

        return EntityCardRedirect::after(
            route('businesses.contacts.index', ['business_id' => $contact->business_id]),
            'Yetkili pasife alındı.',
            route('businesses.show', $contact->business_id),
            'contacts',
        );
    }
}
