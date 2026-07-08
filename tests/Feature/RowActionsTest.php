<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RowActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            CitySeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }

    public function test_business_index_row_actions_include_working_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();
        $business = Business::factory()->create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.index'));

        $response->assertOk();
        $response->assertSee('business-detail', false);
        $response->assertSee('businessListPage', false);
        $response->assertSee(route('businesses.contacts.index', ['business_id' => $business->id]), false);
    }

    public function test_finance_revenue_index_supports_row_action_modal_dispatch(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.revenues.index'));

        $response->assertOk();
        $response->assertSee('finance-row-action', false);
        $response->assertSee('handleRowAction', false);
    }
}
