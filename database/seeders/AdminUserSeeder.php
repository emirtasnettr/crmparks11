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

        $users = [
            [
                'name' => 'Süper Admin',
                'email' => 'admin@crmlog.com',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Genel Müdür',
                'email' => 'mudur@crmlog.com',
                'role' => 'general_manager',
            ],
            [
                'name' => 'Operasyon Yöneticisi',
                'email' => 'operasyon@crmlog.com',
                'role' => 'operations_manager',
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'user_type' => UserType::Internal,
                    'status' => Status::Active,
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole($data['role']);
        }
    }

    private function resolvePassword(): ?string
    {
        if (app()->environment(['local', 'testing'])) {
            return 'password';
        }

        $configuredPassword = env('ADMIN_INITIAL_PASSWORD');

        if (! is_string($configuredPassword) || trim($configuredPassword) === '') {
            return null;
        }

        AdminInitialPasswordPolicy::validate($configuredPassword);

        return $configuredPassword;
    }
}
