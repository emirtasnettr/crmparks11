<?php

namespace App\Modules\FormBuilder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FormBuilder\Exports\FormSubmissionsExport;
use App\Modules\FormBuilder\Services\FormBuilderService;
use App\Modules\FormBuilder\Services\FormSubmissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FormSubmissionController extends Controller
{
  public function __construct(
    private readonly FormBuilderService $formService,
    private readonly FormSubmissionService $submissionService,
  ) {}

  public function index(Request $request, int $id): View
  {
    $form = $this->formService->find($id);

    if ($form === null) {
      abort(404);
    }

    $filters = [
      'search' => $request->string('search')->toString(),
      'date_from' => $request->string('date_from')->toString(),
      'date_to' => $request->string('date_to')->toString(),
    ];

    $submissions = $this->submissionService->listForForm($id, $filters);
    $exportableFields = $this->submissionService->exportableFields($form);

    return view('modules.form-builder.submissions.index', [
      'form' => $form,
      'submissions' => $submissions,
      'exportableFields' => $exportableFields,
      'filters' => $filters,
      'submissionCount' => $this->submissionService->countForForm($id),
    ]);
  }

  public function export(Request $request, int $id): BinaryFileResponse
  {
    $form = $this->formService->find($id);

    if ($form === null) {
      abort(404);
    }

    $filters = [
      'search' => $request->string('search')->toString(),
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
