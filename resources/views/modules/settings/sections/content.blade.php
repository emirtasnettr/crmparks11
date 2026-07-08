@switch($section)
    @case('general')
        <x-ui.card title="Genel Ayarlar" class="mb-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input name="system_name" label="Sistem Adı" :value="$settings['system_name']" />
                <x-ui.input name="company_title" label="Şirket Ünvanı" :value="$settings['company_title']" />
                <div class="md:col-span-2">
                    <x-ui.input name="short_description" label="Kısa Açıklama" :value="$settings['short_description']" />
                </div>
                <x-ui.input name="phone" label="Telefon" :value="$settings['phone']" />
                <x-ui.input name="email" type="email" label="Mail" :value="$settings['email']" />
                <x-ui.input name="website" label="Web Sitesi" :value="$settings['website']" />
                <x-ui.select name="default_locale" label="Varsayılan Dil" :selected="$settings['default_locale']" :options="['tr' => 'Türkçe', 'en' => 'İngilizce']" />
                <x-ui.select name="timezone" label="Saat Dilimi" :selected="$settings['timezone']" :options="['Europe/Istanbul' => 'Europe/Istanbul', 'UTC' => 'UTC']" />
                <x-ui.select name="currency" label="Para Birimi" :selected="$settings['currency']" :options="['TRY' => 'TL', 'USD' => '$', 'EUR' => '€']" />
            </div>
        </x-ui.card>
        @break

    @case('company')
        <x-ui.card title="Firma Bilgileri">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input name="company_title" label="Firma Ünvanı" :value="$settings['company_title']" />
                <x-ui.input name="tax_office" label="Vergi Dairesi" :value="$settings['tax_office']" />
                <x-ui.input name="tax_number" label="Vergi No" :value="$settings['tax_number']" />
                <x-ui.input name="mersis" label="MERSİS" :value="$settings['mersis']" />
                <div class="md:col-span-2"><x-ui.textarea name="address" label="Adres" rows="3">{{ $settings['address'] }}</x-ui.textarea></div>
                <x-ui.input name="phone" label="Telefon" :value="$settings['phone']" />
                <x-ui.input name="email" type="email" label="Mail" :value="$settings['email']" />
                <x-ui.input name="website" label="Web Sitesi" :value="$settings['website']" />
            </div>
        </x-ui.card>
        @break

    @case('branding')
        <x-ui.card title="Logo & Görsel Ayarları" class="mb-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-settings.image-upload name="logo" label="Logo" :current-url="$settings['logo_path_url'] ?? null" />
                <x-settings.image-upload name="dark_logo" label="Karanlık Tema Logo" :current-url="$settings['dark_logo_path_url'] ?? null" />
                <x-settings.image-upload name="favicon" label="Favicon" :current-url="$settings['favicon_path_url'] ?? null" />
                <x-settings.image-upload name="login_image" label="Giriş Sayfası Görseli" :current-url="$settings['login_image_path_url'] ?? null" />
                <x-settings.image-upload name="login_background" label="Giriş Sayfası Arka Planı" :current-url="$settings['login_background_path_url'] ?? null" />
                <x-settings.image-upload name="splash_logo" label="Yükleme Ekranı Logosu" :current-url="$settings['splash_logo_path_url'] ?? null" />
            </div>
            <div class="mt-6 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.toggle name="footer_logo_enabled" label="Footer Logosunu Değiştir" :checked="$settings['footer_logo_enabled']" />
            </div>
        </x-ui.card>
        @break

    @case('theme')
        <x-ui.card title="Tema Ayarları">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.select name="theme_mode" label="Tema Modu" :selected="$settings['theme_mode']" :options="['light' => 'Açık Tema', 'dark' => 'Koyu Tema', 'system' => 'Sistem Teması']" />
                <x-ui.input name="font_family" label="Font" :value="$settings['font_family']" />
                <x-ui.input name="card_radius" label="Kart Radius" :value="$settings['card_radius']" />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ana Renk</label>
                    <input type="color" name="primary_color" value="{{ $settings['primary_color'] }}" class="h-10 w-full cursor-pointer rounded-lg border border-gray-300 dark:border-slate-600" />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">İkincil Renk</label>
                    <input type="color" name="secondary_color" value="{{ $settings['secondary_color'] }}" class="h-10 w-full cursor-pointer rounded-lg border border-gray-300 dark:border-slate-600" />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Buton Rengi</label>
                    <input type="color" name="button_color" value="{{ $settings['button_color'] }}" class="h-10 w-full cursor-pointer rounded-lg border border-gray-300 dark:border-slate-600" />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Sidebar Rengi</label>
                    <input type="color" name="sidebar_color" value="{{ $settings['sidebar_color'] }}" class="h-10 w-full cursor-pointer rounded-lg border border-gray-300 dark:border-slate-600" />
                </div>
            </div>
        </x-ui.card>
        @break

    @case('mail')
        <x-ui.card title="Mail Ayarları">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input name="smtp_host" label="SMTP Host" :value="$settings['smtp_host']" />
                <x-ui.input name="smtp_port" type="number" label="SMTP Port" :value="$settings['smtp_port']" />
                <x-ui.input name="smtp_user" label="SMTP Kullanıcı" :value="$settings['smtp_user']" />
                <x-ui.input name="smtp_password" type="password" label="SMTP Şifre" value="" placeholder="••••••••" />
                <x-ui.select name="smtp_encryption" label="Şifreleme" :selected="$settings['smtp_encryption']" :options="['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Yok']" />
                <x-ui.input name="from_name" label="Gönderen Adı" :value="$settings['from_name']" />
                <x-ui.input name="from_email" type="email" label="Gönderen Maili" :value="$settings['from_email']" />
            </div>
            <div class="mt-4">
                <x-ui.button type="button" variant="secondary" @click="$dispatch('settings-test-mail')">Test Maili Gönder</x-ui.button>
            </div>
        </x-ui.card>
        @break

    @case('sms')
        <x-ui.card title="SMS Ayarları">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.select name="provider" label="SMS Firması" :selected="$settings['provider']" :options="['netgsm' => 'Netgsm', 'iletimerkezi' => 'İleti Merkezi', 'twilio' => 'Twilio']" />
                <x-ui.input name="sender_title" label="Başlık" :value="$settings['sender_title']" />
                <x-ui.input name="api_key" label="API Key" :value="$settings['api_key']" />
                <x-ui.input name="api_secret" type="password" label="API Secret" value="" placeholder="••••••••" />
            </div>
            <div class="mt-4">
                <x-ui.button type="button" variant="secondary" @click="$dispatch('settings-test-sms')">Test SMS Gönder</x-ui.button>
            </div>
        </x-ui.card>
        @break

    @case('notifications')
        <x-ui.card title="Bildirim Ayarları">
            <div class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach ([
                    'mail_notifications' => 'Mail Bildirimleri',
                    'sms_notifications' => 'SMS Bildirimleri',
                    'system_notifications' => 'Sistem Bildirimleri',
                    'browser_notifications' => 'Browser Notification',
                    'earning_notifications' => 'Hakediş Bildirimi',
                    'contract_expiry_notifications' => 'Sözleşme Bitişi',
                    'document_expiry_notifications' => 'Evrak Süresi',
                    'collection_reminder_notifications' => 'Tahsilat Hatırlatma',
                    'payment_reminder_notifications' => 'Ödeme Hatırlatma',
                ] as $field => $label)
                    <div class="py-3">
                        <x-ui.toggle :name="$field" :label="$label" :checked="$settings[$field]" />
                    </div>
                @endforeach
            </div>
        </x-ui.card>
        @break

    @case('finance')
        <x-ui.card title="Finans Ayarları">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input name="default_vat" type="number" label="Varsayılan KDV (%)" :value="$settings['default_vat']" />
                <x-ui.input name="earning_rounding" label="Hakediş Yuvarlama" :value="$settings['earning_rounding']" />
                <x-ui.input name="default_payment_term_days" type="number" label="Varsayılan Vade (Gün)" :value="$settings['default_payment_term_days']" />
                <x-ui.select name="default_currency" label="Varsayılan Para Birimi" :selected="$settings['default_currency']" :options="['TRY' => 'TL', 'USD' => '$', 'EUR' => '€']" />
                <x-ui.input name="invoice_number_format" label="Fatura Numarası Formatı" :value="$settings['invoice_number_format']" />
                <x-ui.input name="current_account_format" label="Cari Kod Formatı" :value="$settings['current_account_format']" />
                <x-ui.input name="revenue_code_format" label="Gelir Kod Formatı" :value="$settings['revenue_code_format']" />
                <x-ui.input name="expense_code_format" label="Gider Kod Formatı" :value="$settings['expense_code_format']" />
            </div>
        </x-ui.card>
        @break

    @case('earnings')
        <x-ui.card title="Hakediş Ayarları">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-ui.radio-group name="default_period" label="Varsayılan Hakediş Dönemi" :selected="$settings['default_period']" :options="['weekly' => 'Haftalık', 'biweekly' => '15 Günlük', 'monthly' => 'Aylık']" />
                <x-ui.radio-group name="approval_process" label="Hakediş Onay Süreci" :selected="$settings['approval_process']" :options="['single' => 'Tek Onay', 'dual' => 'Çift Onay', 'auto' => 'Otomatik Onay']" />
            </div>
        </x-ui.card>
        @break

    @case('files')
        <x-ui.card title="Dosya Ayarları">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input name="max_file_size_mb" type="number" label="Maksimum Dosya Boyutu (MB)" :value="$settings['max_file_size_mb']" />
                <x-ui.input name="retention_days" type="number" label="Dosya Saklama Süresi (Gün)" :value="$settings['retention_days']" />
            </div>
            <div class="mt-6">
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-slate-300">İzin Verilen Dosyalar</p>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                    @foreach (['allowed_pdf' => 'PDF', 'allowed_docx' => 'DOCX', 'allowed_xlsx' => 'XLSX', 'allowed_png' => 'PNG', 'allowed_jpg' => 'JPG', 'allowed_zip' => 'ZIP'] as $field => $label)
                        <x-ui.toggle :name="$field" :label="$label" :checked="$settings[$field]" />
                    @endforeach
                </div>
            </div>
        </x-ui.card>
        @break

    @case('security')
        <x-ui.card title="Güvenlik Ayarları">
            <div class="space-y-4">
                <x-ui.toggle name="two_factor_enabled" label="2FA (İki Faktörlü Doğrulama)" :checked="$settings['two_factor_enabled']" />
                <x-ui.toggle name="password_policy_enabled" label="Şifre Politikası" :checked="$settings['password_policy_enabled']" />
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.input name="min_password_length" type="number" label="Minimum Şifre Uzunluğu" :value="$settings['min_password_length']" />
                    <x-ui.input name="session_lifetime_minutes" type="number" label="Oturum Süresi (Dakika)" :value="$settings['session_lifetime_minutes']" />
                    <x-ui.input name="failed_login_limit" type="number" label="Başarısız Giriş Limiti" :value="$settings['failed_login_limit']" />
                </div>
                <x-ui.toggle name="ip_restriction_enabled" label="IP Kısıtlaması" :checked="$settings['ip_restriction_enabled']" />
            </div>
        </x-ui.card>
        @break

    @case('login')
        <x-ui.card title="Giriş Ayarları">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input name="welcome_text" label="Hoşgeldiniz Yazısı" :value="$settings['welcome_text']" />
                <x-ui.input name="login_title" label="Giriş Sayfası Başlığı" :value="$settings['login_title']" />
                <div class="md:col-span-2"><x-ui.textarea name="login_description" label="Giriş Sayfası Açıklaması" rows="3">{{ $settings['login_description'] }}</x-ui.textarea></div>
                <x-settings.image-upload name="login_logo" label="Logo" :current-url="$settings['login_logo_path_url'] ?? null" />
                <x-settings.image-upload name="login_background" label="Arka Plan" :current-url="$settings['login_background_path_url'] ?? null" />
            </div>
        </x-ui.card>
        @break

    @case('api')
        <x-ui.card title="API Ayarları">
            <div class="grid grid-cols-1 gap-4">
                <x-ui.input name="api_key" label="API Key" :value="$settings['api_key']" />
                <x-ui.input name="webhook_url" label="Webhook URL" :value="$settings['webhook_url']" />
                <x-ui.input name="bearer_token" label="Bearer Token" :value="$settings['bearer_token']" />
                <x-ui.toggle name="api_enabled" label="API Durumu (Aktif)" :checked="$settings['api_enabled']" />
            </div>
        </x-ui.card>
        @break

    @case('backup')
        <x-ui.card title="Yedekleme" class="mb-6">
            <dl class="mb-6 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500 dark:text-slate-400">Son Yedek Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($settings['last_backup_at'])->format('d.m.Y H:i') }}</dd>
                </div>
            </dl>
            <div class="space-y-3">
                <x-ui.toggle name="auto_daily" label="Otomatik Günlük" :checked="$settings['auto_daily']" />
                <x-ui.toggle name="auto_weekly" label="Haftalık" :checked="$settings['auto_weekly']" />
                <x-ui.toggle name="auto_monthly" label="Aylık" :checked="$settings['auto_monthly']" />
            </div>
        </x-ui.card>
        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('settings-backup-manual')">Manuel Yedek Al</x-ui.button>
            <x-ui.button type="button" variant="secondary">Yedekleri Görüntüle</x-ui.button>
        </div>
        @break

    @case('system')
        <x-ui.card title="Sistem Bilgileri">
            <p class="mb-4 text-xs text-amber-700 dark:text-amber-400">Bu bilgiler salt okunurdur.</p>
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @foreach ([
                    'app_version' => 'CRMLog Versiyonu',
                    'laravel_version' => 'Laravel Versiyonu',
                    'php_version' => 'PHP Versiyonu',
                    'mysql_version' => 'MySQL',
                    'server_software' => 'Sunucu Bilgisi',
                    'disk_usage_percent' => 'Disk Kullanımı (%)',
                    'memory_usage_mb' => 'Bellek Kullanımı (MB)',
                    'license_status' => 'Lisans Durumu',
                    'license_expires_at' => 'Lisans Bitiş',
                ] as $key => $label)
                    <div class="rounded-lg border border-gray-100 p-3 dark:border-slate-700">
                        <dt class="text-gray-500 dark:text-slate-400">{{ $label }}</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $settings[$key] }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.card>
        @break
@endswitch

<div x-show="testMessage" x-cloak class="fixed bottom-6 right-6 z-50 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-lg dark:border-emerald-800/50 dark:bg-emerald-900/20 dark:text-emerald-300" x-text="testMessage"></div>
