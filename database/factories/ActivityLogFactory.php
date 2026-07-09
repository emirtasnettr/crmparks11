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
}
