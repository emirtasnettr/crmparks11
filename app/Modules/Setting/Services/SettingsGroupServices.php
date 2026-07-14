<?php

namespace App\Modules\Setting\Services;

use App\Modules\Setting\Contracts\SettingsGroupRepositoryInterface;
use App\Modules\Setting\Contracts\SettingsGroupServiceInterface;
use App\Modules\Setting\Data\SettingsCategoryRegistry;
use App\Modules\Setting\Data\SettingsDefaults;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class AbstractSettingsGroupService implements SettingsGroupServiceInterface
{
  public function __construct(protected SettingsGroupRepositoryInterface $repository) {}

  abstract public function key(): string;

  /**
   * @return array<string, mixed>
   */
  abstract public function defaults(): array;

  /**
   * @return array<string, string|array>
   */
  public function rules(): array
  {
    return [];
  }

  public function all(): array
  {
    return array_merge($this->defaults(), $this->repository->get($this->key()));
  }

  public function save(array $data): array
  {
    if ($this->key() === 'system') {
      return $this->all();
    }

    $validator = Validator::make($data, $this->rules());

    if ($validator->fails()) {
      throw new ValidationException($validator);
    }

    $payload = array_merge($this->all(), $validator->validated());
    $this->repository->put($this->key(), $payload);

    return $payload;
  }

  public function reset(): array
  {
    if ($this->key() === 'system') {
      return $this->defaults();
    }

    $this->repository->forget($this->key());

    return $this->defaults();
  }
}

class GeneralSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'general';
  }

  public function defaults(): array
  {
    return SettingsDefaults::general();
  }

  public function rules(): array
  {
    return [
      'system_name' => 'required|string|max:120',
      'short_description' => 'nullable|string|max:255',
      'company_title' => 'nullable|string|max:255',
      'phone' => 'nullable|string|max:30',
      'email' => 'nullable|email|max:120',
      'website' => 'nullable|url|max:255',
      'default_locale' => 'required|in:tr,en',
      'timezone' => 'required|string|max:64',
      'currency' => 'required|in:TRY,USD,EUR',
    ];
  }
}

class CompanySettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'company';
  }

  public function defaults(): array
  {
    return SettingsDefaults::company();
  }

  public function rules(): array
  {
    return [
      'company_title' => 'required|string|max:255',
      'tax_office' => 'nullable|string|max:120',
      'tax_number' => 'nullable|string|max:20',
      'mersis' => 'nullable|string|max:20',
      'address' => 'nullable|string|max:500',
      'phone' => 'nullable|string|max:30',
      'email' => 'nullable|email|max:120',
      'website' => 'nullable|url|max:255',
    ];
  }
}

class BrandingSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'branding';
  }

  public function defaults(): array
  {
    return SettingsDefaults::branding();
  }

  public function rules(): array
  {
    return [
      'logo_path' => 'nullable|string|max:255',
      'dark_logo_path' => 'nullable|string|max:255',
      'favicon_path' => 'nullable|string|max:255',
      'login_image_path' => 'nullable|string|max:255',
      'login_background_path' => 'nullable|string|max:255',
      'splash_logo_path' => 'nullable|string|max:255',
      'footer_logo_enabled' => 'boolean',
    ];
  }
}

class ThemeSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'theme';
  }

  public function defaults(): array
  {
    return SettingsDefaults::theme();
  }

  public function rules(): array
  {
    return [
      'theme_mode' => 'required|in:light,dark,system',
      'primary_color' => 'required|string|max:20',
      'secondary_color' => 'required|string|max:20',
      'button_color' => 'required|string|max:20',
      'sidebar_color' => 'required|string|max:20',
      'card_radius' => 'required|string|max:20',
      'font_family' => 'required|string|max:80',
    ];
  }
}

class MailSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'mail';
  }

  public function defaults(): array
  {
    return SettingsDefaults::mail();
  }

  public function rules(): array
  {
    return [
      'smtp_host' => 'required|string|max:120',
      'smtp_port' => 'required|integer|min:1|max:65535',
      'smtp_user' => 'nullable|string|max:120',
      'smtp_password' => 'nullable|string|max:255',
      'smtp_encryption' => 'required|in:tls,ssl,none',
      'from_name' => 'required|string|max:120',
      'from_email' => 'required|email|max:120',
    ];
  }
}

class SmsSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'sms';
  }

  public function defaults(): array
  {
    return SettingsDefaults::sms();
  }

  public function rules(): array
  {
    return [
      'provider' => 'required|string|max:80',
      'api_key' => 'nullable|string|max:255',
      'api_secret' => 'nullable|string|max:255',
      'sender_title' => 'nullable|string|max:20',
    ];
  }
}

class NotificationSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'notifications';
  }

  public function defaults(): array
  {
    return SettingsDefaults::notifications();
  }

  public function rules(): array
  {
    return [
      'mail_notifications' => 'boolean',
      'sms_notifications' => 'boolean',
      'system_notifications' => 'boolean',
      'browser_notifications' => 'boolean',
      'earning_notifications' => 'boolean',
      'contract_expiry_notifications' => 'boolean',
      'document_expiry_notifications' => 'boolean',
      'collection_reminder_notifications' => 'boolean',
      'payment_reminder_notifications' => 'boolean',
    ];
  }
}

class FinanceSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'finance';
  }

  public function defaults(): array
  {
    return SettingsDefaults::finance();
  }

  public function rules(): array
  {
    return [
      'default_vat' => 'required|numeric|min:0|max:100',
      'earning_rounding' => 'required|string|max:5',
      'default_payment_term_days' => 'required|integer|min:0|max:365',
      'default_currency' => 'required|in:TRY,USD,EUR',
      'invoice_number_format' => 'required|string|max:80',
      'current_account_format' => 'required|string|max:80',
      'revenue_code_format' => 'required|string|max:80',
      'expense_code_format' => 'required|string|max:80',
    ];
  }
}

class EarningSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'earnings';
  }

  public function defaults(): array
  {
    return SettingsDefaults::earnings();
  }

  public function rules(): array
  {
    return [
      'default_period' => 'required|in:weekly,biweekly,monthly',
      'approval_process' => 'required|in:single,dual,auto',
    ];
  }
}

class FileSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'files';
  }

  public function defaults(): array
  {
    return SettingsDefaults::files();
  }

  public function rules(): array
  {
    return [
      'max_file_size_mb' => 'required|integer|min:1|max:250',
      'allowed_pdf' => 'boolean',
      'allowed_docx' => 'boolean',
      'allowed_xlsx' => 'boolean',
      'allowed_png' => 'boolean',
      'allowed_jpg' => 'boolean',
      'allowed_zip' => 'boolean',
      'retention_days' => 'required|integer|min:30|max:3650',
    ];
  }
}

class SecuritySettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'security';
  }

  public function defaults(): array
  {
    return SettingsDefaults::security();
  }

  public function rules(): array
  {
    return [
      'two_factor_enabled' => 'boolean',
      'password_policy_enabled' => 'boolean',
      'min_password_length' => 'required|integer|min:6|max:64',
      'session_lifetime_minutes' => 'required|integer|min:15|max:1440',
      'ip_restriction_enabled' => 'boolean',
      'failed_login_limit' => 'required|integer|min:3|max:20',
    ];
  }
}

class LoginSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'login';
  }

  public function defaults(): array
  {
    return SettingsDefaults::login();
  }

  public function rules(): array
  {
    return [
      'welcome_text' => 'required|string|max:120',
      'login_title' => 'required|string|max:120',
      'login_description' => 'nullable|string|max:255',
      'login_logo_path' => 'nullable|string|max:255',
      'login_background_path' => 'nullable|string|max:255',
    ];
  }
}

class ApiSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'api';
  }

  public function defaults(): array
  {
    return SettingsDefaults::api();
  }

  public function rules(): array
  {
    return [
      'api_key' => 'required|string|max:120',
      'webhook_url' => 'nullable|url|max:255',
      'bearer_token' => 'nullable|string|max:255',
      'api_enabled' => 'boolean',
    ];
  }
}

class BackupSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'backup';
  }

  public function defaults(): array
  {
    return SettingsDefaults::backup();
  }

  public function rules(): array
  {
    return [
      'last_backup_at' => 'nullable|string',
      'auto_daily' => 'boolean',
      'auto_weekly' => 'boolean',
      'auto_monthly' => 'boolean',
    ];
  }
}

class SystemSettingsService extends AbstractSettingsGroupService
{
  public function key(): string
  {
    return 'system';
  }

  public function defaults(): array
  {
    return SettingsDefaults::system();
  }

  public function save(array $data): array
  {
    return $this->all();
  }

  public function reset(): array
  {
    return $this->defaults();
  }
}

class SettingsGroupServices
{
  /**
   * @return array<string, SettingsGroupServiceInterface>
   */
  public static function registry(SettingsGroupRepositoryInterface $repository): array
  {
    return [
      'general' => new GeneralSettingsService($repository),
      'company' => new CompanySettingsService($repository),
      'branding' => new BrandingSettingsService($repository),
      'theme' => new ThemeSettingsService($repository),
      'mail' => new MailSettingsService($repository),
      'sms' => new SmsSettingsService($repository),
      'notifications' => new NotificationSettingsService($repository),
      'finance' => new FinanceSettingsService($repository),
      'earnings' => new EarningSettingsService($repository),
      'files' => new FileSettingsService($repository),
      'security' => new SecuritySettingsService($repository),
      'login' => new LoginSettingsService($repository),
      'api' => new ApiSettingsService($repository),
      'backup' => new BackupSettingsService($repository),
      'system' => new SystemSettingsService($repository),
    ];
  }
}
