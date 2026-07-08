<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessCourierAssignment> */
class BusinessCourierAssignmentFactory extends Factory
{
    protected $model = BusinessCourierAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'courier_id' => Courier::factory(),
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => null,
            'status' => 'active',
            'notes' => null,
            'assigned_by' => User::factory(),
        ];
    }
}
