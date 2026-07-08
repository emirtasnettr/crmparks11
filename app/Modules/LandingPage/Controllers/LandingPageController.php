<?php

namespace App\Modules\LandingPage\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FormBuilder\Repositories\FormBuilderRepository;
use App\Modules\FormBuilder\Services\FormSubmissionService;
use App\Modules\LandingPage\Services\LandingPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
  public function __construct(
    private readonly LandingPageService $service,
    private readonly FormBuilderRepository $formRepository,
    private readonly FormSubmissionService $submissionService,
  ) {}

  public function show(string $slug): View
  {
    $page = $this->service->findPublicBySlug($slug);

    if ($page === null) {
      abort(404);
    }

    $hasFileField = collect($page['form_fields'] ?? [])->contains(fn (array $field) => ($field['type'] ?? '') === 'file');

    return view('landing.show', [
      'page' => $page,
      'hasFileField' => $hasFileField,
    ]);
  }

  public function submit(Request $request, string $slug): RedirectResponse
  {
    $page = $this->service->findPublicBySlug($slug);

    if ($page === null || empty($page['form_id'])) {
      abort(404);
    }

    $form = $this->formRepository->find((int) $page['form_id']);

    if ($form === null) {
      abort(404);
    }

    $this->submissionService->storeFromLanding($request, $form, $page);

    return redirect()
      ->route('landing.show', $slug)
      ->with('form_success', 'Başvurunuz başarıyla alındı. Teşekkür ederiz.');
  }
}
