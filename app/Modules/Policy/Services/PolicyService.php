<?php

namespace App\Modules\Policy\Services;

use App\Modules\LandingPage\Support\LandingPageContentSanitizer;
use App\Modules\Policy\Data\PolicyDefinitions;
use App\Modules\Policy\Repositories\PolicyRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PolicyService
{
  public function __construct(private readonly PolicyRepository $repository) {}

  /**
   * @return array<string, array<string, mixed>>
   */
  public function allForAdmin(): array
  {
    return collect($this->repository->all())
      ->map(fn (array $policy, string $key) => $this->enrich($policy, $key))
      ->all();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findPublicBySlug(string $slug): ?array
  {
    $policy = $this->repository->findBySlug($slug);

    return $policy ? $this->enrich($policy, $policy['key']) : null;
  }

  /**
   * @return array<int, array{label: string, url: string}>
   */
  public function footerLinks(): array
  {
    return collect(PolicyDefinitions::all())
      ->map(fn (array $definition) => [
        'label' => $definition['label'],
        'url' => route('policy.show', $definition['slug']),
      ])
      ->values()
      ->all();
  }

  /**
   * @param  array<string, array<string, mixed>>  $data
   * @return array<string, array<string, mixed>>
   */
  public function updateAll(array $data): array
  {
    $policies = $this->repository->all();
    $now = Carbon::now()->toDateTimeString();

    foreach (PolicyDefinitions::keys() as $key) {
      $input = $data[$key] ?? [];
      $validated = $this->validatePolicy($input, $key);

      $policies[$key] = array_merge($policies[$key] ?? [], [
        'key' => $key,
        'slug' => PolicyDefinitions::all()[$key]['slug'],
        'title' => $validated['title'] ?? PolicyDefinitions::all()[$key]['title'],
        'content' => LandingPageContentSanitizer::clean($validated['content'] ?? ''),
        'meta_title' => $validated['meta_title'] ?? PolicyDefinitions::all()[$key]['title'],
        'meta_description' => $validated['meta_description'] ?? '',
        'updated_at' => $now,
      ]);
    }

    $this->repository->write($policies);

    return $this->allForAdmin();
  }

  /**
   * @param  array<string, mixed>  $policy
   * @return array<string, mixed>
   */
  private function enrich(array $policy, string $key): array
  {
    $definition = PolicyDefinitions::all()[$key] ?? [];

    return array_merge($policy, [
      'label' => $definition['label'] ?? $policy['title'] ?? '',
      'public_url' => ! empty($policy['slug']) ? route('policy.show', $policy['slug']) : null,
      'updated_at_formatted' => Carbon::parse($policy['updated_at'] ?? now())->format('d.m.Y H:i'),
    ]);
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  private function validatePolicy(array $data, string $key): array
  {
    $validator = Validator::make($data, [
      'title' => 'nullable|string|max:255',
      'content' => 'nullable|string|max:50000',
      'meta_title' => 'nullable|string|max:70',
      'meta_description' => 'nullable|string|max:160',
    ]);

    if ($validator->fails()) {
      throw ValidationException::withMessages([
        $key => $validator->errors()->first(),
      ]);
    }

    return $validator->validated();
  }
}
