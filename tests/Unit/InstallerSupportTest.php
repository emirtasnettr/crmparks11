<?php

namespace Tests\Unit;

use App\Support\AdminInitialPasswordPolicy;
use App\Support\Installer\EnvironmentConfigurator;
use App\Support\Installer\InstallLock;
use InvalidArgumentException;
use Tests\TestCase;

class InstallerSupportTest extends TestCase
{
    public function test_admin_password_policy_rejects_weak_passwords(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AdminInitialPasswordPolicy::validate('password');
    }

    public function test_admin_password_policy_rejects_short_passwords(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AdminInitialPasswordPolicy::validate('short');
    }

    public function test_admin_password_policy_accepts_strong_password(): void
    {
        $this->assertTrue(AdminInitialPasswordPolicy::isValid('MySecurePass99!'));
    }

    public function test_environment_configurator_updates_values(): void
    {
        $path = storage_path('framework/testing-install.env');
        file_put_contents($path, "APP_NAME=Laravel\n# DB_HOST=127.0.0.1\n");

        $configurator = new EnvironmentConfigurator($path);
        $configurator
            ->set('APP_NAME', 'CRMLog')
            ->set('DB_HOST', '10.0.0.5')
            ->save();

        $contents = file_get_contents($path);

        $this->assertStringContainsString('APP_NAME=CRMLog', $contents);
        $this->assertStringContainsString('DB_HOST=10.0.0.5', $contents);
        $this->assertStringNotContainsString('# DB_HOST', $contents);

        @unlink($path);
    }

    public function test_install_lock_can_be_written_and_removed(): void
    {
        InstallLock::remove();

        InstallLock::write(['app_url' => 'https://example.com']);

        $this->assertTrue(InstallLock::exists());
        $this->assertSame('https://example.com', InstallLock::read()['app_url'] ?? null);

        InstallLock::remove();

        $this->assertFalse(InstallLock::exists());
    }
}
