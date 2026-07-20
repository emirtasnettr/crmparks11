<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use App\Modules\Courier\Models\CourierVehicle;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierVehicleBankWorkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }

    public function test_vehicle_can_be_deactivated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create(['created_by' => $user->id]);
        $vehicle = CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('couriers.vehicles.deactivate', $vehicle->id))
            ->assertRedirect(route('couriers.vehicles.index', ['courier_id' => $courier->id]));

        $this->assertSame('inactive', $vehicle->fresh()->status);
    }

    public function test_bank_account_can_be_made_default_and_deactivated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create(['created_by' => $user->id]);

        $primary = CourierBankAccount::query()->create([
            'courier_id' => $courier->id,
            'bank_key' => 'ziraat',
            'account_holder' => 'Ali Veli',
            'iban' => 'TR330006100519786457841326',
            'is_default' => true,
            'status' => 'active',
        ]);

        $secondary = CourierBankAccount::query()->create([
            'courier_id' => $courier->id,
            'bank_key' => 'garanti',
            'account_holder' => 'Ali Veli',
            'iban' => 'TR320010009999901234567890',
            'is_default' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('couriers.bank-accounts.make-default', $secondary->id))
            ->assertRedirect();

        $this->assertFalse($primary->fresh()->is_default);
        $this->assertTrue($secondary->fresh()->is_default);

        $this->actingAs($user)
            ->post(route('couriers.bank-accounts.deactivate', $secondary->id))
            ->assertRedirect();

        $this->assertSame('inactive', $secondary->fresh()->status);
        $this->assertFalse($secondary->fresh()->is_default);
    }

}
