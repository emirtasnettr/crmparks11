<?php

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Data\StockActivityFormData;
use App\Modules\Stock\Data\StockFormData;
use App\Modules\Stock\Requests\StoreStockAssignmentRequest;
use App\Modules\Stock\Requests\StoreStockProductRequest;
use App\Modules\Stock\Requests\UpdateStockProductRequest;
use App\Modules\Stock\Services\StockActivityPresenter;
use App\Modules\Stock\Services\StockActivityService;
use App\Modules\Stock\Services\StockAssignmentService;
use App\Modules\Stock\Services\StockInventoryService;
use App\Modules\Stock\Services\StockProductPresenter;
use App\Modules\Stock\Services\StockProductService;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockProductController extends Controller
{
    public function __construct(
        private readonly StockProductService $products,
        private readonly StockProductPresenter $presenter,
        private readonly StockAssignmentService $assignments,
        private readonly StockActivityService $activities,
        private readonly StockActivityPresenter $activityPresenter,
        private readonly StockInventoryService $inventory,
    ) {}

    public function dashboard(): View
    {
        $payload = $this->inventory->dashboard();

        return view('modules.stock.inventory.dashboard', $payload);
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => RequestFilter::valueOrAll($request, 'status'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $all = $this->products->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($product) => $this->presenter->indexRow($product))
            ->values()
            ->all();

        return view('modules.stock.products.index', [
            'products' => $items,
            'filters' => $filters,
            'statuses' => StockFormData::statuses(),
            'summary' => $this->products->summary($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function create(): View
    {
        return view('modules.stock.products.create', [
            'statuses' => StockFormData::statuses(),
            'units' => StockFormData::units(),
        ]);
    }

    public function store(StoreStockProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated(), $request->user());

        return redirect()
            ->route('stock.products.show', $product->id)
            ->with('success', 'Ürün kartı oluşturuldu.');
    }

    public function show(int $id): View
    {
        $product = $this->products->find($id);
        abort_if($product === null, 404);

        return view('modules.stock.products.show', [
            'product' => $this->presenter->showPayload($product),
            'couriers' => $this->products->courierOptions(),
        ]);
    }

    public function edit(int $id): View
    {
        $product = $this->products->find($id);
        abort_if($product === null, 404);

        return view('modules.stock.products.edit', [
            'product' => $this->presenter->showPayload($product),
            'formValues' => $this->presenter->formPayload($product),
            'statuses' => StockFormData::statuses(),
            'units' => StockFormData::units(),
        ]);
    }

    public function update(UpdateStockProductRequest $request, int $id): RedirectResponse
    {
        $product = $this->products->find($id);
        abort_if($product === null, 404);

        $this->products->update($product, $request->validated());

        return redirect()
            ->route('stock.products.show', $id)
            ->with('success', 'Ürün kartı güncellendi.');
    }

    public function assign(StoreStockAssignmentRequest $request, int $id): RedirectResponse
    {
        $product = $this->products->find($id);
        abort_if($product === null, 404);

        $this->products->assign($product, $request->validated(), $request->user());

        return redirect()
            ->route('stock.products.show', $id)
            ->with('success', 'Ekipman kuryeye zimmetlendi; stok güncellendi.');
    }

    public function returnAssignment(Request $request, int $assignmentId): RedirectResponse
    {
        abort_unless($request->user()?->can('stock.update'), 403);

        $assignment = \App\Modules\Stock\Models\StockAssignment::query()->find($assignmentId);
        abort_if($assignment === null, 404);

        $this->products->returnAssignment($assignment, $request->user());

        return redirect()
            ->back()
            ->with('success', 'Zimmet iade alındı; stok güncellendi.');
    }

    public function assignmentsIndex(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->has('status')
                ? RequestFilter::valueOrAll($request, 'status')
                : 'assigned',
            'courier_id' => RequestFilter::valueOrAll($request, 'courier_id'),
            'product_id' => RequestFilter::valueOrAll($request, 'product_id'),
        ];

        $all = $this->assignments->filter($filters);
        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return view('modules.stock.assignments.index', [
            'assignments' => $this->presenter->assignmentIndexRows($items),
            'filters' => $filters,
            'statuses' => StockFormData::assignmentStatuses(),
            'couriers' => $this->products->courierOptions(),
            'products' => $this->products->filter(['status' => 'all'])->map(fn ($p) => [
                'id' => $p->id,
                'label' => $p->name,
            ]),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function activityIndex(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'action' => RequestFilter::valueOrAll($request, 'action'),
            'user_id' => RequestFilter::valueOrAll($request, 'user_id'),
            'product_id' => RequestFilter::valueOrAll($request, 'product_id'),
            'date_range' => RequestFilter::valueOrAll($request, 'date_range'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $all = $this->activities->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($log) => $this->activityPresenter->indexRow($log))
            ->values()
            ->all();

        return view('modules.stock.activity.index', [
            'activities' => $items,
            'filters' => $filters,
            'actionTypes' => StockActivityFormData::actionTypes(),
            'dateRanges' => StockActivityFormData::dateRanges(),
            'users' => $this->activities->users(),
            'products' => $this->activities->products(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ]);
    }
}
