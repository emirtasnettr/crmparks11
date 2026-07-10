<?php

namespace App\Console\Commands;

use App\Support\AdminInitialPasswordPolicy;
use App\Support\Installer\EnvironmentConfigurator;
use App\Support\Installer\InstallLock;
use App\Support\Installer\PrerequisiteChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CrmLogInstallCommand extends Command
{
    protected $signature = 'crmlog:install
        {--force : Mevcut kurulum kilidini yok say}
        {--skip-build : Frontend derlemesi kontrolünü atla}
        {--no-cache : Kurulum sonrası cache oluşturma}
        {--app-name=CRMLog : Uygulama adı}
        {--app-url= : Uygulama URL adresi}
        {--db-connection=mysql : Veritabanı sürücüsü (mysql veya sqlite)}
        {--db-host=127.0.0.1 : MySQL sunucu adresi}
        {--db-port=3306 : MySQL portu}
        {--db-database= : Veritabanı adı}
        {--db-username= : Veritabanı kullanıcı adı}
        {--db-password= : Veritabanı şifresi (tercihen INSTALL_DB_PASSWORD)}
        {--admin-password= : Admin şifresi (tercihen INSTALL_ADMIN_PASSWORD ortam değişkeni)}';

    protected $description = 'CRMLog production kurulum sihirbazı (yalnızca CLI)';

    public function handle(): int
    {
        if (! $this->input->isInteractive() && ! $this->option('no-interaction')) {
            $this->components->warn('Etkileşimsiz ortam algılandı. --no-interaction kullanın.');
        }

        if (InstallLock::exists() && ! $this->option('force')) {
            $metadata = InstallLock::read();
            $installedAt = is_array($metadata) ? ($metadata['installed_at'] ?? 'bilinmiyor') : 'bilinmiyor';

            $this->components->error("CRMLog zaten kurulu ({$installedAt}).");
            $this->line('Yeniden kurulum için: php artisan crmlog:install --force');

            return self::FAILURE;
        }

        if ($this->option('force') && InstallLock::exists()) {
            if (! $this->confirmReinstall()) {
                $this->components->warn('Kurulum iptal edildi.');

                return self::FAILURE;
            }

            InstallLock::remove();
        }

        $checker = new PrerequisiteChecker;
        $failures = $checker->failures(requireFrontendBuild: ! $this->option('skip-build'));

        if ($failures !== []) {
            $this->components->error('Ön koşullar sağlanamadı:');

            foreach ($failures as $failure) {
                $this->line("  - {$failure}");
            }

            return self::FAILURE;
        }

        try {
            $config = $this->gatherConfiguration();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Kurulum başlıyor...');

        try {
            $this->writeEnvironment($config);
            $this->reloadEnvironment();
            $this->applyRuntimeConfiguration($config);
            $this->ensureApplicationKey();
            $this->applyRuntimeConfiguration($config);
            $this->verifyDatabaseConnection($config);
            $this->runMigrationsAndSeeders($config);
            $this->runStorageLink();
            $this->applyRuntimeConfiguration($config);
            $this->optimizeApplication();
            InstallLock::write([
                'app_url' => $config['app_url'],
                'app_name' => $config['app_name'],
                'db_connection' => $config['db_connection'],
                'php_version' => PHP_VERSION,
            ]);
        } catch (Throwable $exception) {
            $this->components->error('Kurulum başarısız: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('CRMLog kurulumu tamamlandı.');
        $this->line('Giriş adresi: '.$config['app_url'].'/login');
        $this->line('Süper Admin: admin@crmlog.com');
        $this->components->warn('Admin şifresi yalnızca kurulum sırasında belirlediğiniz değerdir; tekrar gösterilmez.');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     app_name: string,
     *     app_url: string,
     *     db_connection: string,
     *     db_host: string,
     *     db_port: string,
     *     db_database: string,
     *     db_username: string,
     *     db_password: string,
     *     admin_password: string,
     *     secure_cookies: bool
     * }
     */
    private function gatherConfiguration(): array
    {
        $appName = (string) $this->option('app-name');
        $appUrl = $this->resolveAppUrl();
        $dbConnection = strtolower((string) $this->option('db-connection'));

        if (! in_array($dbConnection, ['mysql', 'sqlite'], true)) {
            throw new \InvalidArgumentException('Desteklenen veritabanı sürücüleri: mysql, sqlite.');
        }

        $config = [
            'app_name' => $appName !== '' ? $appName : 'CRMLog',
            'app_url' => $appUrl,
            'db_connection' => $dbConnection,
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => '',
            'db_username' => '',
            'db_password' => '',
            'admin_password' => '',
            'secure_cookies' => str_starts_with($appUrl, 'https://'),
        ];

        if ($dbConnection === 'mysql') {
            $config['db_host'] = $this->resolveOptionOrAsk('db-host', 'MySQL sunucu adresi', '127.0.0.1');
            $config['db_port'] = $this->resolveOptionOrAsk('db-port', 'MySQL portu', '3306');
            $config['db_database'] = $this->resolveOptionOrAsk('db-database', 'Veritabanı adı');
            $config['db_username'] = $this->resolveOptionOrAsk('db-username', 'Veritabanı kullanıcı adı');
            $config['db_password'] = $this->resolveSecret(
                optionName: 'db-password',
                envKey: 'INSTALL_DB_PASSWORD',
                prompt: 'Veritabanı şifresi'
            );
        } else {
            $databasePath = database_path('database.sqlite');

            if (! is_file($databasePath)) {
                touch($databasePath);
            }

            $config['db_database'] = $databasePath;
        }

        $config['admin_password'] = $this->resolveAdminPassword();

        return $config;
    }

    private function resolveAppUrl(): string
    {
        $provided = $this->option('app-url');

        if (is_string($provided) && trim($provided) !== '') {
            return EnvironmentConfigurator::validateAppUrl($provided);
        }

        if ($this->option('no-interaction')) {
            throw new \InvalidArgumentException('Etkileşimsiz kurulum için --app-url zorunludur.');
        }

        do {
            $url = $this->ask('Uygulama URL adresi (ör. https://crm.alanadiniz.com)');
            $valid = true;

            try {
                $url = EnvironmentConfigurator::validateAppUrl((string) $url);
            } catch (\InvalidArgumentException $exception) {
                $this->components->error($exception->getMessage());
                $valid = false;
            }
        } while (! $valid);

        return $url;
    }

    private function resolveOptionOrAsk(string $optionName, string $question, ?string $default = null): string
    {
        $provided = $this->option($optionName);

        if (is_string($provided) && trim($provided) !== '') {
            return trim($provided);
        }

        if ($this->option('no-interaction')) {
            throw new \InvalidArgumentException("Etkileşimsiz kurulum için --{$optionName} zorunludur.");
        }

        $value = $this->ask($question, $default);

        if (! is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException("{$question} boş bırakılamaz.");
        }

        return trim($value);
    }

    private function resolveSecret(string $optionName, string $envKey, string $prompt): string
    {
        $fromEnv = env($envKey);

        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        $fromOption = $this->option($optionName);

        if (is_string($fromOption) && $fromOption !== '') {
            return $fromOption;
        }

        if ($this->option('no-interaction')) {
            throw new \InvalidArgumentException("Etkileşimsiz kurulum için {$envKey} ortam değişkeni zorunludur.");
        }

        return (string) $this->secret($prompt);
    }

    private function resolveAdminPassword(): string
    {
        $fromEnv = env('INSTALL_ADMIN_PASSWORD');

        if (is_string($fromEnv) && $fromEnv !== '') {
            AdminInitialPasswordPolicy::validate($fromEnv);

            return $fromEnv;
        }

        $fromOption = $this->option('admin-password');

        if (is_string($fromOption) && $fromOption !== '') {
            AdminInitialPasswordPolicy::validate($fromOption);

            return $fromOption;
        }

        if ($this->option('no-interaction')) {
            throw new \InvalidArgumentException('Etkileşimsiz kurulum için INSTALL_ADMIN_PASSWORD ortam değişkeni zorunludur.');
        }

        do {
            $password = (string) $this->secret('Admin şifresi (en az 12 karakter)');
            $confirmation = (string) $this->secret('Admin şifresini tekrar girin');
            $valid = true;

            if (! hash_equals($password, $confirmation)) {
                $this->components->error('Şifreler eşleşmiyor.');
                $valid = false;
            }

            try {
                AdminInitialPasswordPolicy::validate($password);
            } catch (\InvalidArgumentException $exception) {
                $this->components->error($exception->getMessage());
                $valid = false;
            }
        } while (! $valid);

        return $password;
    }

    /**
     * @param  array{
     *     app_name: string,
     *     app_url: string,
     *     db_connection: string,
     *     db_host: string,
     *     db_port: string,
     *     db_database: string,
     *     db_username: string,
     *     db_password: string,
     *     admin_password: string,
     *     secure_cookies: bool
     * }  $config
     */
    private function writeEnvironment(array $config): void
    {
        $env = new EnvironmentConfigurator;

        $env
            ->set('APP_NAME', $config['app_name'])
            ->set('APP_ENV', 'production')
            ->set('APP_DEBUG', 'false')
            ->set('APP_URL', $config['app_url'])
            ->set('LOG_LEVEL', 'warning')
            ->set('DB_CONNECTION', $config['db_connection'])
            ->set('SESSION_DRIVER', 'database')
            ->set('SESSION_ENCRYPT', 'true')
            ->set('SESSION_SECURE_COOKIE', $config['secure_cookies'] ? 'true' : 'false')
            ->set('SESSION_SAME_SITE', 'lax')
            ->set('CACHE_STORE', 'database')
            ->set('QUEUE_CONNECTION', 'database')
            ->set('ADMIN_INITIAL_PASSWORD', $config['admin_password']);

        if ($config['db_connection'] === 'mysql') {
            $env
                ->set('DB_HOST', $config['db_host'])
                ->set('DB_PORT', $config['db_port'])
                ->set('DB_DATABASE', $config['db_database'])
                ->set('DB_USERNAME', $config['db_username'])
                ->set('DB_PASSWORD', $config['db_password']);
        } else {
            $env->set('DB_DATABASE', $config['db_database']);
        }

        $env->save();

        $this->components->twoColumnDetail('.env', '<fg=green>DÜZENLENDİ</>');
    }

    private function reloadEnvironment(): void
    {
        $this->callSilent('config:clear');

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $dotenv = \Dotenv\Dotenv::createMutable(base_path());
        $dotenv->load();
    }

    /**
     * @param  array{
     *     db_connection: string,
     *     db_host: string,
     *     db_port: string,
     *     db_database: string,
     *     db_username: string,
     *     db_password: string
     * }  $config
     */
    private function applyRuntimeConfiguration(array $config): void
    {
        config([
            'database.default' => $config['db_connection'],
            'database.connections.mysql.host' => $config['db_host'],
            'database.connections.mysql.port' => $config['db_port'],
            'database.connections.mysql.database' => $config['db_database'],
            'database.connections.mysql.username' => $config['db_username'],
            'database.connections.mysql.password' => $config['db_password'],
            'database.connections.sqlite.database' => $config['db_connection'] === 'sqlite'
                ? $config['db_database']
                : database_path('database.sqlite'),
            'cache.stores.database.connection' => null,
            'queue.connections.database.connection' => null,
        ]);

        DB::purge('mysql');
        DB::purge('sqlite');
        DB::setDefaultConnection($config['db_connection']);
    }

    private function ensureApplicationKey(): void
    {
        if (is_string(config('app.key')) && config('app.key') !== '') {
            return;
        }

        $this->callSilent('key:generate', ['--force' => true]);
        $this->reloadEnvironment();
        $this->components->twoColumnDetail('APP_KEY', '<fg=green>OLUŞTURULDU</>');
    }

    /**
     * @param  array{
     *     db_connection: string,
     *     db_host: string,
     *     db_port: string,
     *     db_database: string,
     *     db_username: string,
     *     db_password: string
     * }  $config
     */
    private function verifyDatabaseConnection(array $config): void
    {
        config([
            'database.default' => $config['db_connection'],
            'database.connections.mysql.host' => $config['db_host'],
            'database.connections.mysql.port' => $config['db_port'],
            'database.connections.mysql.database' => $config['db_database'],
            'database.connections.mysql.username' => $config['db_username'],
            'database.connections.mysql.password' => $config['db_password'],
            'database.connections.sqlite.database' => $config['db_database'],
        ]);

        DB::purge($config['db_connection']);

        try {
            DB::connection($config['db_connection'])->getPdo();
        } catch (Throwable $exception) {
            throw new \RuntimeException('Veritabanı bağlantısı kurulamadı: '.$exception->getMessage(), previous: $exception);
        }

        $this->components->twoColumnDetail('Veritabanı', '<fg=green>BAĞLANDI</>');
    }

    private function runMigrationsAndSeeders(array $config): void
    {
        $this->applyRuntimeConfiguration($config);

        $this->callSilent('migrate', ['--force' => true]);
        $this->components->twoColumnDetail('Migration', '<fg=green>TAMAM</>');

        $this->applyRuntimeConfiguration($config);

        $this->callSilent('db:seed', ['--force' => true]);
        $this->components->twoColumnDetail('Seed', '<fg=green>TAMAM</>');
    }

    private function runStorageLink(): void
    {
        if (is_link(public_path('storage'))) {
            $this->components->twoColumnDetail('storage:link', '<fg=yellow>ZATEN VAR</>');

            return;
        }

        $this->callSilent('storage:link');
        $this->components->twoColumnDetail('storage:link', '<fg=green>TAMAM</>');
    }

    private function optimizeApplication(): void
    {
        if ($this->option('no-cache')) {
            return;
        }

        foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
            $this->callSilent($command);
        }

        $this->components->twoColumnDetail('Cache', '<fg=green>OLUŞTURULDU</>');
    }

    private function confirmReinstall(): bool
    {
        if ($this->option('no-interaction')) {
            return true;
        }

        $token = Str::upper(Str::random(6));
        $answer = $this->ask("Yeniden kurulum tehlikelidir. Onaylamak için şunu yazın: {$token}");

        return is_string($answer) && hash_equals($token, Str::upper(trim($answer)));
    }
}
