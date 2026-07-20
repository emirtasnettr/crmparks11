<?php

namespace Database\Seeders;

use App\Core\Enums\Status;
use App\Core\Enums\UserType;
use App\Models\User;
use App\Support\AdminInitialPasswordPolicy;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = $this->resolvePassword();

        if ($password === null) {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@crmlog.com'],
            [
                'name' => 'Süper Admin',
                'password' => $password,
                'user_type' => UserType::Internal,
                'status' => Status::Active,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super_admin']);
    }

    private function resolvePassword(): ?string
    {
        if (app()->environment(['local', 'testing'])) {
            return 'password';
        }

        $configuredPassword = env('ADMIN_INITIAL_PASSWORD');

        if (! is_string($configuredPassword) || trim($configuredPassword) === '') {
            $configuredPassword = $this->readAdminPasswordFromEnvFile();
        }

        if (! is_string($configuredPassword) || trim($configuredPassword) === '') {
            return null;
        }

        AdminInitialPasswordPolicy::validate($configuredPassword);

        return $configuredPassword;
    }

    private function readAdminPasswordFromEnvFile(): ?string
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false || ! preg_match('/^ADMIN_INITIAL_PASSWORD=(.*)$/m', $contents, $matches)) {
            return null;
        }

        $value = trim($matches[1]);

        if ($value === '' || $value === '""' || $value === "''") {
            return null;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
