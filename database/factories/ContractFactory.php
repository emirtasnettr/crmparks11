<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractType;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contract> */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->subMonths(2);
        $end = now()->addMonths(10);

        return [
            'contractable_type' => Business::class,
            'contractable_id' => Business::factory(),
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'title' => 'Hizmet Sözleşmesi',
            'contract_number' => 'SZL-'.fake()->unique()->numerify('####'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'auto_reminder' => true,
            'reminder_days_before' => 30,
            'status' => 'active',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
