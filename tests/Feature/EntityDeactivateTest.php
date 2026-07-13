<?php

namespace Tests\Feature;

use App\Core\Enums\Status;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityDeactivateTest extends TestCase
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

    public function test_super_admin_can_deactivate_business_courier_and_agency(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['status' => 'active', 'created_by' => $user->id]);
        $courier = Courier::factory()->create(['status' => 'active', 'created_by' => $user->id]);
        $agency = Agency::factory()->create(['status' => 'active', 'created_by' => $user->id]);

        $this->actingAs($user)->post(route('businesses.deactivate', $business->id), [
            'contract_end_date' => now()->toDateString(),
            'notes' => 'Sözleşme sonlandırıldı',
        ])
            ->assertRedirect(route('businesses.index'))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $business->fresh()->status);
        $this->assertSame(now()->toDateString(), $business->fresh()->contract_end_date?->toDateString());

        $this->actingAs($user)->post(route('couriers.deactivate', $courier->id))
            ->assertRedirect(route('couriers.index'));

        $this->actingAs($user)->post(route('agencies.deactivate', $agency->id))
            ->assertRedirect(route('agencies.index'));

        $this->assertSame('inactive', $courier->fresh()->status);
        $this->assertSame('inactive', $agency->fresh()->status);
    }

    public function test_super_admin_can_suspend_and_deactivate_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $target = User::factory()->create(['status' => Status::Active]);
        $target->assignRole('operations_specialist');

        $this->actingAs($admin)->post(route('users.suspend', $target->id))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertSame(Status::Suspended, $target->fresh()->status);

        $this->actingAs($admin)->post(route('users.deactivate', $target->id))
            ->assertRedirect(route('users.index'));

        $this->assertSame(Status::Inactive, $target->fresh()->status);
    }
}
