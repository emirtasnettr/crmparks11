<?php

namespace App\Modules\FormBuilder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FormBuilder\Data\FormFieldTypes;
use App\Modules\FormBuilder\Services\FormBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormBuilderController extends Controller
{
  public function __construct(private readonly FormBuilderService $service) {}

  public function index(Request $request): View
  {
    $filters = [
      'search' => $request->string('search')->toString(),
      'status' => $request->string('status')->toString() ?: 'all',
    ];

    return view('modules.form-builder.index', [
      'forms' => $this->service->list($filters),
      'filters' => $filters,
    ]);
  }

  public function create(): View
  {
    return view('modules.form-builder.create');
  }

  public function store(Request $request): RedirectResponse
  {
    $form = $this->service->create($request->only(['name', 'description', 'status']));

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

    return view('modules.form-builder.edit', [
      'form' => $form,
      'fieldTypes' => FormFieldTypes::palette(),
      'fieldTypeLabels' => FormFieldTypes::labels(),
    ]);
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
}
