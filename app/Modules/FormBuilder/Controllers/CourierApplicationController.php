<?php

namespace App\Modules\FormBuilder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FormBuilder\Services\FormSubmissionService;
use App\Modules\FormBuilder\Services\FormSubmissionStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierApplicationController extends Controller
{
    public function __construct(
        private readonly FormSubmissionService $submissionService,
        private readonly FormSubmissionStatusService $statusService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status_id' => $request->string('status_id')->toString() ?: 'all',
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $statuses = $this->statusService->list();
        $submissions = $this->submissionService->listAll($filters);

        return view('modules.courier-applications.index', [
            'submissions' => $submissions,
            'filters' => $filters,
            'statuses' => $statuses,
            'statusFilterOptions' => collect($statuses)
                ->mapWithKeys(fn (array $status) => [(string) $status['id'] => $status['name']])
                ->prepend('Tümü', 'all')
                ->all(),
            'submissionCount' => count($submissions),
        ]);
    }

    public function show(int $submissionId): View
    {
        $payload = $this->submissionService->findWithForm($submissionId);

        if ($payload === null) {
            abort(404);
        }

        return view('modules.courier-applications.show', [
            'form' => $payload['form'],
            'submission' => $payload['submission'],
            'notes' => $this->submissionService->notesForSubmission($submissionId),
            'statuses' => $this->statusService->list(),
        ]);
    }

    public function updateStatus(Request $request, int $submissionId): RedirectResponse
    {
        $validated = $request->validate([
            'form_submission_status_id' => ['required', 'integer', 'exists:form_submission_statuses,id'],
        ], [
            'form_submission_status_id.required' => 'Statü seçiniz.',
        ]);

        $payload = $this->submissionService->findWithForm($submissionId);

        if ($payload === null) {
            abort(404);
        }

        $this->submissionService->updateStatus(
            (int) $payload['form']['id'],
            $submissionId,
            (int) $validated['form_submission_status_id'],
        );

        return redirect()
            ->route('courier-applications.show', $submissionId)
            ->with('success', 'Başvuru statüsü güncellendi.');
    }

    public function storeNote(Request $request, int $submissionId): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'body.required' => 'Not metni zorunludur.',
        ]);

        $payload = $this->submissionService->findWithForm($submissionId);

        if ($payload === null) {
            abort(404);
        }

        $this->submissionService->addNote(
            (int) $payload['form']['id'],
            $submissionId,
            $validated['body'],
            $request->user()?->id,
        );

        return redirect()
            ->route('courier-applications.show', $submissionId)
            ->with('success', 'Not kaydedildi.');
    }
}
