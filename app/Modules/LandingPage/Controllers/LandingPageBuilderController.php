<?php

namespace App\Modules\LandingPage\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandingPage\Services\LandingPageService;
use App\Modules\LandingPage\Support\LandingPageHero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageBuilderController extends Controller
{
  public function __construct(private readonly LandingPageService $service) {}

  public function index(Request $request): View
  {
    $filters = [
      'search' => $request->string('search')->toString(),
      'status' => $request->string('status')->toString() ?: 'all',
    ];

    return view('modules.landing-page-builder.index', [
      'pages' => $this->service->list($filters),
      'filters' => $filters,
    ]);
  }

  public function create(): View
  {
    return view('modules.landing-page-builder.create', [
      'forms' => $this->service->formOptions(),
    ]);
  }

  public function store(Request $request): RedirectResponse
  {
    $page = $this->service->create($request->all());

    return redirect()
      ->route('landing-page-builder.edit', $page['id'])
      ->with('success', 'Landing page oluşturuldu.');
  }

  public function edit(int $id): View
  {
    $page = $this->service->find($id);

    if ($page === null) {
      abort(404);
    }

    return view('modules.landing-page-builder.edit', [
      'page' => $page,
      'forms' => $this->service->formOptions(),
      'heroSpec' => [
        'recommended' => LandingPageHero::recommendedSizeLabel(),
        'minimum' => LandingPageHero::minimumSizeLabel(),
        'aspectClass' => LandingPageHero::aspectClass(),
      ],
    ]);
  }

  public function update(Request $request, int $id): RedirectResponse
  {
    $this->service->update($id, $request->all(), $request->file('hero_image'));

    return redirect()
      ->route('landing-page-builder.edit', $id)
      ->with('success', 'Landing page kaydedildi.');
  }

  public function destroy(int $id): RedirectResponse
  {
    $this->service->delete($id);

    return redirect()
      ->route('landing-page-builder.index')
      ->with('success', 'Landing page silindi.');
  }
}
