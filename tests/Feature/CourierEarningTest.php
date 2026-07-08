<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Data\CourierEarningDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierEarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_earnings_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.earnings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_earnings_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.earnings.index'));

        $response->assertRedirect(route('couriers.index'));
    }

    public function test_authenticated_user_can_view_earning_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.earnings.show', 1));

        $response->assertRedirect(route('couriers.index'));
    }

    public function test_net_payment_is_calculated_correctly(): void
    {
        $earning = CourierEarningDummyData::find(1);

        $this->assertNotNull($earning);
        $this->assertEquals(47800, $earning['net_payment']);
    }

    public function test_soft_deleted_earnings_are_excluded_from_list(): void
    {
        $active = CourierEarningDummyData::all();
        $withTrashed = CourierEarningDummyData::all(true);

        $this->assertCount(33, $active);
        $this->assertCount(35, $withTrashed);
        $this->assertNull(CourierEarningDummyData::find(34));
        $this->assertNull(CourierEarningDummyData::find(35));
        $this->assertNotNull(CourierEarningDummyData::find(34, true));
    }

    public function test_soft_deleted_earning_detail_returns_not_found(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.earnings.show', 34));

        $response->assertRedirect(route('couriers.index'));
    }

    public function test_earnings_can_be_filtered_by_payment_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.earnings.index', [
            'payment_status' => 'cancelled',
        ]));

        $response->assertRedirect(route('couriers.index'));
    }

    public function test_earnings_can_be_filtered_by_courier(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.earnings.index', [
            'courier_id' => 4,
        ]));

        $response->assertRedirect(route('couriers.index'));
    }
}
