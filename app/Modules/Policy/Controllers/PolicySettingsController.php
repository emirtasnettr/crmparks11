<?php

namespace App\Modules\Policy\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Policy\Services\PolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PolicySettingsController extends Controller
{
  public function __construct(private readonly PolicyService $service) {}

  public function index(): RedirectResponse
  {
    return redirect()->route('settings.index', ['section' => 'policies']);
  }

  public function update(Request $request): RedirectResponse
  {
    $this->service->updateAll($request->all());

    return redirect()
      ->route('settings.index', ['section' => 'policies'])
      ->with('success', 'Politika sayfaları kaydedildi.');
  }
}
