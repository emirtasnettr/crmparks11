<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => 'courier_created',
            'subject_type' => Courier::class,
            'subject_id' => Courier::factory(),
            'description' => fake()->sentence(),
            'old_values' => null,
            'new_values' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ];
    }

    public function login(): static
    {
        return $this->state(fn () => [
            'action' => 'login',
            'subject_type' => User::class,
            'subject_id' => null,
            'description' => 'Kullanıcı giriş yaptı',
            'old_values' => [],
            'new_values' => ['session_id' => 'sess_test'],
        ]);
    }

    public function loginFailed(): static
    {
        return $this->state(fn () => [
            'action' => 'login_failed',
            'subject_type' => null,
            'subject_id' => null,
            'description' => 'Başarısız giriş denemesi',
            'old_values' => ['email' => 'unknown@example.com'],
            'new_values' => ['reason' => 'invalid_credentials'],
        ]);
    }

    public function permissionUpdated(): static
    {
        return $this->state(fn () => [
            'action' => 'permission_updated',
            'subject_type' => null,
            'subject_id' => null,
            'description' => 'Rol yetkileri güncellendi',
            'old_values' => ['permissions' => ['dashboard.view']],
            'new_values' => ['permissions' => ['dashboard.view', 'report.export']],
        ]);
    }
}
