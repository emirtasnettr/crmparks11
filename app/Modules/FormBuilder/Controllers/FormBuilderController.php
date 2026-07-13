<?php

namespace App\Modules\FormBuilder\Controllers;

use App\Core\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\FormBuilder\Data\FormFieldTypes;
use App\Modules\FormBuilder\Services\FormBuilderService;
use App\Modules\FormBuilder\Services\FormSubmissionStatusService;
use App\Modules\User\Data\UserManagementFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class FormBuilderController extends Controller
{
  public function __construct(
    private readonly FormBuilderService $service,
    private readonly FormSubmissionStatusService $statusService,
  ) {}

  public function index(Request $request): View
  {
    $filters = [
      'search' => $request->string('search')->toString(),
      'status' => $request->string('status')->toString() ?: 'all',
    ];

    return view('modules.form-builder.index', [
      'forms' => $this->service->list($filters),
      'filters' => $filters,
      'submissionStatuses' => $this->statusService->list(),
      'openStatusSettings' => $request->boolean('statuses'),
    ]);
  }

  public function create(): View
  {
    return view('modules.form-builder.create', $this->recipientOptions());
  }

  public function store(Request $request): RedirectResponse
  {
    $form = $this->service->create($request->only([
      'name',
      'description',
      'status',
      'notify_user_ids',
      'notify_roles',
    ]));

    return redirect()
      ->route('form-builder.edit', $form['id'])
      ->with('success', 'Form oluşturuldu. Alanları ekleyebilirsiniz.');
  }

  public function edit(int $id): View
  {
    $form = $this->service->find($id);

    if ($form === null) {
      abort(404);
    }

    return view('modules.form-builder.edit', array_merge([
      'form' => $form,
      'fieldTypes' => FormFieldTypes::palette(),
      'fieldTypeLabels' => FormFieldTypes::labels(),
    ], $this->recipientOptions()));
  }

  public function update(Request $request, int $id): RedirectResponse
  {
    $this->service->update($id, $request->all());

    return redirect()
      ->route('form-builder.edit', $id)
      ->with('success', 'Form kaydedildi.');
  }

  public function destroy(int $id): RedirectResponse
  {
    $this->service->delete($id);

    return redirect()
      ->route('form-builder.index')
      ->with('success', 'Form silindi.');
  }

  /**
   * @return array{notifyUsers: array<int, string>, notifyRoles: array<string, string>}
   */
  private function recipientOptions(): array
  {
    $roleLabels = UserManagementFormData::roleLabels();

    return [
      'notifyUsers' => User::query()
        ->where('status', Status::Active)
        ->orderBy('name')
        ->get(['id', 'name', 'email'])
        ->mapWithKeys(fn (User $user) => [
          $user->id => $user->name.' ('.$user->email.')',
        ])
        ->all(),
      'notifyRoles' => Role::query()
        ->orderBy('name')
        ->pluck('name')
        ->filter(fn (string $name) => isset($roleLabels[$name]))
        ->mapWithKeys(fn (string $name) => [$name => $roleLabels[$name]])
        ->all(),
    ];
  }
}
