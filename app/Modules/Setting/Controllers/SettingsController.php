<?php

namespace App\Modules\Setting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Setting\Data\SettingsCategoryRegistry;
use App\Modules\Setting\Services\SettingsManager;
use App\Modules\Setting\Services\SettingsMediaService;
use App\Modules\Policy\Services\PolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class SettingsController extends Controller
{
  public function __construct(
    private readonly SettingsManager $manager,
    private readonly SettingsMediaService $media,
  ) {}

  public function index(Request $request, PolicyService $policyService): View
  {
    $section = $this->manager->resolveSection($request->string('section')->toString() ?: null);

    if ($section === 'policies') {
      return view('modules.settings.index', [
        'section' => $section,
        'categories' => $this->manager->categories(),
        'settings' => [],
        'policies' => $policyService->allForAdmin(),
        'categoryMeta' => $this->manager->categories()[$section],
        'isReadOnly' => false,
        'isPolicySection' => true,
      ]);
    }

    $service = $this->manager->group($section);
    $settings = $service->all();

    foreach ($settings as $key => $value) {
      if (is_string($value) && str_ends_with($key, '_path') && $value) {
        $settings[$key.'_url'] = $this->media->url($value);
      }
    }

    return view('modules.settings.index', [
      'section' => $section,
      'categories' => $this->manager->categories(),
      'settings' => $settings,
      'categoryMeta' => $this->manager->categories()[$section],
      'isReadOnly' => $section === 'system',
      'isPolicySection' => false,
    ]);
  }

  public function update(Request $request, string $group): RedirectResponse
  {
    $group = $this->manager->resolveSection($group);
    $service = $this->manager->group($group);

    if ($group === 'system') {
      return redirect()
        ->route('settings.index', ['section' => $group])
        ->with('error', 'Sistem bilgileri salt okunurdur.');
    }

    $data = $request->except(['_token', '_method']);

    /** @var UploadedFile $file */
    foreach ($request->allFiles() as $field => $file) {
      if (! $file->isValid()) {
        continue;
      }

      $stored = $this->media->store($file, $group, $field);
      $pathKey = str_ends_with($field, '_path') ? $field : $field.'_path';
      $data[$pathKey] = $stored['path'];
      unset($data[$field]);
    }

    foreach ($service->defaults() as $key => $default) {
      if (is_bool($default)) {
        $data[$key] = $request->boolean($key);
      }
    }

    if (empty($data['smtp_password'])) {
      unset($data['smtp_password']);
    }

    if (empty($data['api_secret'])) {
      unset($data['api_secret']);
    }

    $service->save($data);

    return redirect()
      ->route('settings.index', ['section' => $group])
      ->with('success', SettingsCategoryRegistry::label($group).' kaydedildi.');
  }

  public function reset(string $group): RedirectResponse
  {
    $group = $this->manager->resolveSection($group);
    $service = $this->manager->group($group);

    if ($group === 'system') {
      return redirect()
        ->route('settings.index', ['section' => $group])
        ->with('error', 'Sistem bilgileri sıfırlanamaz.');
    }

    $service->reset();

    return redirect()
      ->route('settings.index', ['section' => $group])
      ->with('success', 'Ayarlar varsayılan değerlere döndürüldü.');
  }
}
