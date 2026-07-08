<?php

namespace App\Modules\Setting\Services;

class AppBrandingService
{
  public function __construct(
    private readonly SettingsManager $manager,
    private readonly SettingsMediaService $media,
  ) {}

  /**
   * @return array<string, mixed>
   */
  public function resolve(): array
  {
    $general = $this->manager->group('general')->all();
    $branding = $this->manager->group('branding')->all();
    $login = $this->manager->group('login')->all();

    $logoUrl = $this->media->url($branding['logo_path'] ?? null);
    $darkLogoUrl = $this->media->url($branding['dark_logo_path'] ?? null) ?? $logoUrl;
    $loginLogoUrl = $this->media->url($login['login_logo_path'] ?? null) ?? $logoUrl;

    $loginBackgroundUrl = $this->media->url($login['login_background_path'] ?? null)
      ?? $this->media->url($branding['login_background_path'] ?? null);

    return [
      'system_name' => $general['system_name'] ?? config('crmlog.name'),
      'short_description' => $general['short_description'] ?? '',
      'logo_url' => $logoUrl,
      'dark_logo_url' => $darkLogoUrl,
      'favicon_url' => $this->media->url($branding['favicon_path'] ?? null),
      'login_logo_url' => $loginLogoUrl,
      'login_image_url' => $this->media->url($branding['login_image_path'] ?? null),
      'login_background_url' => $loginBackgroundUrl,
      'splash_logo_url' => $this->media->url($branding['splash_logo_path'] ?? null),
      'welcome_text' => $login['welcome_text'] ?? 'Hoş geldiniz',
      'login_title' => $login['login_title'] ?? 'Hesabınıza giriş yapın',
      'login_description' => $login['login_description'] ?? '',
      'has_logo' => $logoUrl !== null,
      'has_login_logo' => $loginLogoUrl !== null,
    ];
  }
}
