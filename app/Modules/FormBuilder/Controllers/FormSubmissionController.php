<?php

namespace App\Modules\FormBuilder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FormBuilder\Exports\FormSubmissionsExport;
use App\Modules\FormBuilder\Services\FormBuilderService;
use App\Modules\FormBuilder\Services\FormSubmissionService;
use App\Modules\FormBuilder\Services\FormSubmissionStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FormSubmissionController extends Controller
{
  public function __construct(
    private readonly FormBuilderService $formService,
    private readonly FormSubmissionService $submissionService,
    private readonly FormSubmissionStatusService $statusService,
  ) {}

  public function index(Request $request, int $id): View
  {
    $form = $this->formService->find($id);

    if ($form === null) {
      abort(404);
    }

    $filters = [
      'search' => $request->string('search')->toString(),
      'status_id' => $request->string('status_id')->toString() ?: 'all',
      'date_from' => $request->string('date_from')->toString(),
      'date_to' => $request->string('date_to')->toString(),
    ];

    $submissions = $this->submissionService->listForForm($id, $filters);
    $exportableFields = $this->submissionService->exportableFields($form);
    $statuses = $this->statusService->list();

    return view('modules.form-builder.submissions.index', [
      'form' => $form,
      'submissions' => $submissions,
      'exportableFields' => $exportableFields,
      'filters' => $filters,
      'statuses' => $statuses,
      'statusFilterOptions' => collect($statuses)
        ->mapWithKeys(fn (array $status) => [(string) $status['id'] => $status['name']])
        ->prepend('Tümü', 'all')
        ->all(),
      'submissionCount' => $this->submissionService->countForForm($id),
    ]);
  }

  public function show(int $id, int $submissionId): View
  {
    $form = $this->formService->find($id);

    if ($form === null) {
      abort(404);
    }

    $submission = $this->submissionService->findForForm($id, $submissionId);

    if ($submission === null) {
      abort(404);
    }

    return view('modules.form-builder.submissions.show', [
      'form' => $form,
      'submission' => $submission,
      'notes' => $this->submissionService->notesForSubmission($submissionId),
      'statuses' => $this->statusService->list(),
    ]);
  }

  public function updateStatus(Request $request, int $id, int $submissionId): RedirectResponse
  {
    $validated = $request->validate([
      'form_submission_status_id' => ['required', 'integer', 'exists:form_submission_statuses,id'],
    ], [
      'form_submission_status_id.required' => 'Statü seçiniz.',
    ]);

    $this->submissionService->updateStatus($id, $submissionId, (int) $validated['form_submission_status_id']);

    return redirect()
      ->route('form-builder.submissions.show', [$id, $submissionId])
      ->with('success', 'Başvuru statüsü güncellendi.');
  }

  public function storeNote(Request $request, int $id, int $submissionId): RedirectResponse
  {
    $validated = $request->validate([
      'body' => ['required', 'string', 'max:5000'],
    ], [
      'body.required' => 'Not metni zorunludur.',
    ]);

    $submission = $this->submissionService->findForForm($id, $submissionId);

    if ($submission === null) {
      abort(404);
    }

    $this->submissionService->addNote(
      $id,
      $submissionId,
      $validated['body'],
      $request->user()?->id,
    );

    return redirect()
      ->route('form-builder.submissions.show', [$id, $submissionId])
      ->with('success', 'Not kaydedildi.');
  }

  public function export(Request $request, int $id): BinaryFileResponse
  {
    $form = $this->formService->find($id);

    if ($form === null) {
      abort(404);
    }

    $filters = [
      'search' => $request->string('search')->toString(),
      'status_id' => $request->string('status_id')->toString() ?: 'all',
      'date_from' => $request->string('date_from')->toString(),
      'date_to' => $request->string('date_to')->toString(),
    ];

    $submissions = $this->submissionService->listForForm($id, $filters);
    $exportableFields = $this->submissionService->exportableFields($form);
    $filename = str($form['name'])->slug().'-basvurular-'.now()->format('Y-m-d').'.xlsx';

    return Excel::download(
      new FormSubmissionsExport($form, $submissions, $exportableFields),
      $filename
    );
  }
}
