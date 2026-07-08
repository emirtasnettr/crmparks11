<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Installer\InstallLock;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use Tests\TestCase;

#[RunClassInSeparateProcess]
class InstallCommandTest extends TestCase
{
    private string $envBackupPath;

    protected function setUp(): void
    {
        parent::setUp();

        InstallLock::remove();

        $this->envBackupPath = storage_path('framework/testing.env.backup');

        if (is_file(base_path('.env'))) {
            copy(base_path('.env'), $this->envBackupPath);
        } else {
            copy(base_path('.env.example'), base_path('.env'));
            Artisan::call('key:generate', ['--force' => true]);
            copy(base_path('.env'), $this->envBackupPath);
        }
    }

    protected function tearDown(): void
    {
        InstallLock::remove();

        if (is_file($this->envBackupPath)) {
            copy($this->envBackupPath, base_path('.env'));
            unlink($this->envBackupPath);
        }

        parent::tearDown();
    }

    public function test_install_command_refuses_when_lock_exists(): void
    {
        InstallLock::write(['app_url' => 'https://example.com']);

        $exitCode = Artisan::call('crmlog:install');

        $this->assertSame(1, $exitCode);
    }

    public function test_install_command_completes_with_sqlite(): void
    {
        $databasePath = database_path('database.sqlite');

        if (is_file($databasePath)) {
            unlink($databasePath);
        }

        $exitCode = Artisan::call('crmlog:install', [
            '--no-interaction' => true,
            '--skip-build' => true,
            '--no-cache' => true,
            '--force' => true,
            '--app-url' => 'http://localhost',
            '--db-connection' => 'sqlite',
            '--admin-password' => 'SecurePass123!',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(InstallLock::exists());

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $databasePath,
        ]);
        DB::purge('sqlite');

        $user = User::on('sqlite')->where('email', 'admin@crmlog.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('super_admin'));
    }
}
