<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Requests\StoreBusinessAssignmentRequest;
use App\Modules\Business\Requests\UpdateBusinessAssignmentRequest;
use App\Modules\Business\Services\BusinessAssignmentPresenter;
use App\Modules\Business\Services\BusinessAssignmentService;
use App\Support\EntityCardRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessAssignmentController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly BusinessAssignmentService $assignments,
        private readonly BusinessAssignmentPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->assignments->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($assignment) => $this->presenter->indexRow($assignment))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.assignments.index', [
            'assignments' => $items,
            'filters' => $filters,
            'businesses' => $this->assignments->businesses(),
            'agencies' => $this->assignments->agencies(),
            'couriers' => $this->assignments->couriersAvailableForAssignment(),
            'activeCount' => $this->assignments->countActive(),
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
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'atanan-kuryeler',
            BusinessListExportSheets::assignments($filters),
            'Atanan Kuryeler',
        );
    }

    public function show(int $id): View
    {
        $assignment = $this->assignments->find($id);

        abort_if($assignment === null, 404);

        return view('modules.business.assignments.show', [
            'assignment' => $this->presenter->showRow($assignment),
        ]);
    }

    public function store(StoreBusinessAssignmentRequest $request): RedirectResponse
    {
        $assignment = $this->assignments->create($request->validated(), $request->user());

        if ($request->boolean('redirect_to_business')) {
            return EntityCardRedirect::toShow(
                route('businesses.show', $assignment->business_id),
                'assignments',
                'Kurye ataması başarıyla oluşturuldu.',
            );
        }

        return redirect()
            ->route('businesses.assignments.index', ['business_id' => $assignment->business_id])
            ->with('success', 'Kurye ataması başarıyla oluşturuldu.');
    }

    public function update(UpdateBusinessAssignmentRequest $request, int $id): RedirectResponse
    {
        $assignment = $this->assignments->find($id);

        if ($assignment === null) {
            abort(404);
        }

        $this->assignments->update($assignment, $request->validated());

        return redirect()
            ->back()
            ->with('success', 'Atama bilgileri güncellendi.');
    }

    public function terminate(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('assignment.update'), 403);

        $assignment = $this->assignments->find($id);
        abort_if($assignment === null, 404);

        $this->assignments->terminate($assignment);

        return EntityCardRedirect::after(
            route('businesses.assignments.index', ['business_id' => $assignment->business_id]),
            'Atama sonlandırıldı.',
            route('businesses.show', $assignment->business_id),
            'assignments',
        );
    }
}
