<?php

namespace App\Modules\FormBuilder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FormBuilder\Services\FormSubmissionStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormSubmissionStatusController extends Controller
{
    public function __construct(private readonly FormSubmissionStatusService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'in:primary,success,danger,warning,muted'],
        ], [
            'name.required' => 'Statü adı zorunludur.',
        ]);

        $this->service->create($validated['name'], $validated['color'] ?? null);

        return redirect()
            ->route('form-builder.index', ['statuses' => 1])
            ->with('success', 'Statü eklendi.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'in:primary,success,danger,warning,muted'],
        ], [
            'name.required' => 'Statü adı zorunludur.',
        ]);

        $this->service->update($id, $validated['name'], $validated['color'] ?? null);

        return redirect()
            ->route('form-builder.index', ['statuses' => 1])
            ->with('success', 'Statü güncellendi.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->service->delete($id);

        return redirect()
            ->route('form-builder.index', ['statuses' => 1])
            ->with('success', 'Statü silindi.');
    }

    public function setDefault(int $id): RedirectResponse
    {
        $this->service->setDefault($id);

        return redirect()
            ->route('form-builder.index', ['statuses' => 1])
            ->with('success', 'Varsayılan statü güncellendi.');
    }
}
