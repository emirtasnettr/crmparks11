<?php

namespace App\Modules\Setting\Data;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SettingsDefaults
{
  public const REFERENCE_DATE = '2026-07-07';

  /**
   * @return array<string, mixed>
   */
  public static function general(): array
  {
    return [
      'system_name' => 'CRMLog',
      'short_description' => 'Kurye ve lojistik operasyon yönetim platformu',
      'company_title' => 'CRMLog Teknoloji A.Ş.',
      'phone' => '0212 555 00 00',
      'email' => 'info@crmlog.test',
      'website' => 'https://www.crmlog.test',
      'default_locale' => 'tr',
      'timezone' => 'Europe/Istanbul',
      'currency' => 'TRY',
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function company(): array
  {
    return [
      'company_title' => 'CRMLog Teknoloji Anonim Şirketi',
      'tax_office' => 'Maslak',
      'tax_number' => '1234567890',
      'mersis' => '0123456789012345',
      'address' => 'Maslak Mah. Büyükdere Cad. No:123 Sarıyer / İstanbul',
      'phone' => '0212 555 00 00',
      'email' => 'info@crmlog.test',
      'website' => 'https://www.crmlog.test',
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function branding(): array
  {
    return [
      'logo_path' => null,
      'dark_logo_path' => null,
      'favicon_path' => null,
      'login_image_path' => null,
      'login_background_path' => null,
      'splash_logo_path' => null,
      'footer_logo_enabled' => true,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function theme(): array
  {
    return [
      'theme_mode' => 'system',
      'primary_color' => '#2563eb',
      'secondary_color' => '#64748b',
      'button_color' => '#2563eb',
      'sidebar_color' => '#0f172a',
      'card_radius' => '0.75rem',
      'font_family' => 'Instrument Sans',
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function mail(): array
  {
    return [
      'smtp_host' => 'smtp.crmlog.test',
      'smtp_port' => 587,
      'smtp_user' => 'noreply@crmlog.test',
      'smtp_password' => '',
      'smtp_encryption' => 'tls',
      'from_name' => 'CRMLog',
      'from_email' => 'noreply@crmlog.test',
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function sms(): array
  {
    return [
      'provider' => 'netgsm',
      'api_key' => '',
      'api_secret' => '',
      'sender_title' => 'CRMLOG',
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function notifications(): array
  {
    return [
      'mail_notifications' => true,
      'sms_notifications' => false,
      'system_notifications' => true,
      'browser_notifications' => true,
      'earning_notifications' => true,
      'contract_expiry_notifications' => true,
      'document_expiry_notifications' => true,
      'collection_reminder_notifications' => true,
      'payment_reminder_notifications' => true,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function finance(): array
  {
    return [
      'default_vat' => 20,
      'earning_rounding' => '2',
      'default_payment_term_days' => 30,
      'default_currency' => 'TRY',
      'invoice_number_format' => 'FTR-{YEAR}-{SEQ:6}',
      'current_account_format' => 'CAR-{SEQ:6}',
      'revenue_code_format' => 'GLR-{YEAR}-{SEQ:6}',
      'expense_code_format' => 'GDR-{YEAR}-{SEQ:6}',
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function earnings(): array
  {
    return [
      'default_period' => 'monthly',
      'approval_process' => 'dual',
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function files(): array
  {
    return [
      'max_file_size_mb' => 10,
      'allowed_pdf' => true,
      'allowed_docx' => true,
      'allowed_xlsx' => true,
      'allowed_png' => true,
      'allowed_jpg' => true,
      'allowed_zip' => true,
      'retention_days' => 365,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function security(): array
  {
    return [
      'two_factor_enabled' => false,
      'password_policy_enabled' => true,
      'min_password_length' => 8,
      'session_lifetime_minutes' => 120,
      'ip_restriction_enabled' => false,
      'failed_login_limit' => 5,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function login(): array
  {
    return [
      'welcome_text' => 'CRMLog\'a Hoş Geldiniz',
      'login_title' => 'Hesabınıza giriş yapın',
      'login_description' => 'Kurye ve lojistik operasyonlarınızı tek panelden yönetin.',
      'login_logo_path' => null,
      'login_background_path' => null,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function api(): array
  {
    return [
      'api_key' => 'crmlog_live_a1b2c3d4e5f67890',
      'webhook_url' => 'https://hooks.crmlog.test/events',
      'bearer_token' => 'bt_9f8e7d6c5b4a3210fedcba9876543210',
      'api_enabled' => true,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function backup(): array
  {
    return [
      'last_backup_at' => Carbon::parse(self::REFERENCE_DATE)->subHours(6)->toDateTimeString(),
      'auto_daily' => true,
      'auto_weekly' => true,
      'auto_monthly' => true,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function system(): array
  {
    $total = disk_total_space(base_path()) ?: 1;
    $free = disk_free_space(base_path()) ?: 0;
    $usedPercent = (int) round((($total - $free) / $total) * 100);

    return [
      'app_version' => '1.0.0',
      'laravel_version' => app()->version(),
      'php_version' => PHP_VERSION,
      'mysql_version' => self::mysqlVersion(),
      'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? php_sapi_name(),
      'disk_usage_percent' => $usedPercent,
      'memory_usage_mb' => (int) round(memory_get_usage(true) / 1024 / 1024),
      'license_status' => 'active',
      'license_expires_at' => '2027-12-31',
    ];
  }

  private static function mysqlVersion(): string
  {
    try {
      return DB::selectOne('select version() as version')->version ?? 'N/A';
    } catch (\Throwable) {
      return 'N/A (test ortamı)';
    }
  }
}
