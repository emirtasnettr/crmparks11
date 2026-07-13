<?php

namespace App\Modules\FormBuilder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FormBuilder\Services\FormBuilderService;
use App\Modules\FormBuilder\Services\FormSubmissionService;
use App\Modules\FormBuilder\Services\FormSubmissionStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormApplicationController extends Controller
{
    public function __construct(
        private readonly FormBuilderService $formService,
        private readonly FormSubmissionService $submissionService,
        private readonly FormSubmissionStatusService $statusService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        return view('modules.form-applications.index', [
            'forms' => $this->formService->list($filters),
            'filters' => $filters,
        ]);
    }

    public function submissions(Request $request, int $formId): View
    {
        $form = $this->formService->find($formId);

        if ($form === null) {
            abort(404);
        }

        $filters = [
            'search' => $request->string('search')->toString(),
            'status_id' => $request->string('status_id')->toString() ?: 'all',
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $statuses = $this->statusService->list();
        $submissions = $this->submissionService->listForForm($formId, $filters);
        $exportableFields = $this->submissionService->exportableFields($form);

        return view('modules.form-applications.submissions', [
            'form' => $form,
            'submissions' => $submissions,
            'exportableFields' => $exportableFields,
            'filters' => $filters,
            'statuses' => $statuses,
            'statusFilterOptions' => collect($statuses)
                ->mapWithKeys(fn (array $status) => [(string) $status['id'] => $status['name']])
                ->prepend('Tümü', 'all')
                ->all(),
            'submissionCount' => $this->submissionService->countForForm($formId),
        ]);
    }

    public function show(int $formId, int $submissionId): View
    {
        $form = $this->formService->find($formId);
        $submission = $this->submissionService->findForForm($formId, $submissionId);

        if ($form === null || $submission === null) {
            abort(404);
        }

        return view('modules.form-applications.show', [
            'form' => $form,
            'submission' => $submission,
            'notes' => $this->submissionService->notesForSubmission($submissionId),
            'statuses' => $this->statusService->list(),
        ]);
    }

    public function updateStatus(Request $request, int $formId, int $submissionId): RedirectResponse
    {
        $validated = $request->validate([
            'form_submission_status_id' => ['required', 'integer', 'exists:form_submission_statuses,id'],
        ], [
            'form_submission_status_id.required' => 'Statü seçiniz.',
        ]);

        $this->submissionService->updateStatus($formId, $submissionId, (int) $validated['form_submission_status_id']);

        return redirect()
            ->route('form-applications.show', [$formId, $submissionId])
            ->with('success', 'Başvuru statüsü güncellendi.');
    }

    public function storeNote(Request $request, int $formId, int $submissionId): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'body.required' => 'Not metni zorunludur.',
        ]);

        if ($this->submissionService->findForForm($formId, $submissionId) === null) {
            abort(404);
        }

        $this->submissionService->addNote(
            $formId,
            $submissionId,
            $validated['body'],
            $request->user()?->id,
        );

        return redirect()
            ->route('form-applications.show', [$formId, $submissionId])
            ->with('success', 'Not kaydedildi.');
    }
}
