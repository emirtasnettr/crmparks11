<?php

namespace App\Modules\Policy\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Policy\Services\PolicyService;
use Illuminate\View\View;

class PolicyController extends Controller
{
  public function __construct(private readonly PolicyService $service) {}

  public function show(string $slug): View
  {
    $policy = $this->service->findPublicBySlug($slug);

    if ($policy === null) {
      abort(404);
    }

    return view('policy.show', [
      'page' => [
        'slug' => $policy['slug'],
        'title' => $policy['title'],
        'content' => $policy['content'],
        'meta_title' => $policy['meta_title'] ?? $policy['title'],
        'meta_description' => $policy['meta_description'] ?? '',
      ],
    ]);
  }
}
