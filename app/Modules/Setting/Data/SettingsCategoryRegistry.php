<?php

namespace App\Modules\Setting\Data;

class SettingsCategoryRegistry
{
  /**
   * @return array<string, array{label: string, icon: string, description: string}>
   */
  public static function all(): array
  {
    return [
      'general' => ['label' => 'Genel Ayarlar', 'icon' => 'cog', 'description' => 'Sistem kimliği ve varsayılanlar'],
      'company' => ['label' => 'Firma Bilgileri', 'icon' => 'building', 'description' => 'Resmi firma ve vergi bilgileri'],
      'branding' => ['label' => 'Logo & Görsel Ayarları', 'icon' => 'chart', 'description' => 'Logo, favicon ve görseller'],
      'theme' => ['label' => 'Tema Ayarları', 'icon' => 'chart', 'description' => 'Renk, font ve görünüm'],
      'mail' => ['label' => 'Mail Ayarları', 'icon' => 'chart', 'description' => 'SMTP ve gönderim ayarları'],
      'sms' => ['label' => 'SMS Ayarları', 'icon' => 'chart', 'description' => 'SMS sağlayıcı entegrasyonu'],
      'notifications' => ['label' => 'Bildirim Ayarları', 'icon' => 'chart', 'description' => 'Bildirim kanalları ve kuralları'],
      'finance' => ['label' => 'Finans Ayarları', 'icon' => 'earning', 'description' => 'KDV, kod formatları ve finans'],
      'earnings' => ['label' => 'Hakediş Ayarları', 'icon' => 'earning', 'description' => 'Hakediş dönemi ve onay süreci'],
      'files' => ['label' => 'Dosya Ayarları', 'icon' => 'chart', 'description' => 'Dosya boyutu ve türleri'],
      'security' => ['label' => 'Güvenlik Ayarları', 'icon' => 'courier', 'description' => '2FA, şifre ve oturum'],
      'login' => ['label' => 'Giriş Ayarları', 'icon' => 'courier', 'description' => 'Giriş ekranı metinleri'],
      'api' => ['label' => 'API Ayarları', 'icon' => 'chart', 'description' => 'API anahtarları ve webhook'],
      'backup' => ['label' => 'Yedekleme', 'icon' => 'chart', 'description' => 'Yedekleme planı ve geçmişi'],
      'policies' => ['label' => 'Politika Ayarları', 'icon' => 'policy-settings', 'description' => 'KVKK, gizlilik ve çerez sayfaları'],
      'system' => ['label' => 'Sistem Bilgileri', 'icon' => 'chart', 'description' => 'Versiyon ve sunucu bilgileri'],
    ];
  }

  public static function isValid(string $key): bool
  {
    return array_key_exists($key, self::all());
  }

  public static function label(string $key): string
  {
    return self::all()[$key]['label'] ?? $key;
  }
}
