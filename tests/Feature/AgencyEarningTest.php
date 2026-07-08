<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyEarningDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyEarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_agency_earnings_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.earnings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_earnings_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.earnings.index'));

        $response->assertRedirect(route('agencies.index'));
    }

    public function test_agency_earnings_have_at_least_thirty_records(): void
    {
        $earnings = AgencyEarningDummyData::all();

        $this->assertCount(31, $earnings);
        $this->assertGreaterThanOrEqual(30, count($earnings));
    }

    public function test_net_payment_is_calculated_correctly(): void
    {
        $earning = AgencyEarningDummyData::find(1);

        $this->assertNotNull($earning);
        $this->assertEquals(62700.0, $earning['net_payment']);
    }

    public function test_soft_deleted_earnings_are_excluded_by_default(): void
    {
        $all = AgencyEarningDummyData::all();
        $withTrashed = AgencyEarningDummyData::all(true);

        $this->assertCount(31, $all);
        $this->assertCount(32, $withTrashed);
        $this->assertNull(AgencyEarningDummyData::find(32));
        $this->assertNotNull(AgencyEarningDummyData::find(32, true));
    }

    public function test_summary_stats_are_calculated(): void
    {
        $summary = AgencyEarningDummyData::summarize();

        $this->assertEquals(31, $summary['count']);
        $this->assertGreaterThan(0, $summary['total_payable']);
        $this->assertGreaterThan(0, $summary['paid_amount']);
        $this->assertGreaterThan(0, $summary['pending_count']);
        $this->assertGreaterThan(0, $summary['this_month_count']);
    }

    public function test_agency_earnings_can_be_filtered_by_payment_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.earnings.index', [
            'payment_status' => 'paid',
        ]));

        $response->assertRedirect(route('agencies.index'));
    }

    public function test_authenticated_user_can_view_agency_earning_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.earnings.show', 1));

        $response->assertRedirect(route('agencies.index'));
    }
}
