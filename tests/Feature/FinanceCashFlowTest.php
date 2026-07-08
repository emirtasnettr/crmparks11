<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceCashFlowDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCashFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_cash_flow_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.cash-flow.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cash_flow_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.cash-flow.index'));

        $response->assertOk();
        $response->assertSee('Nakit Akışı');
        $response->assertSee('Giren ve çıkan tüm nakit hareketlerini analiz edin.');
        $response->assertSee('Kasaya Giren');
        $response->assertSee('Kasadan Çıkan');
        $response->assertSee('Net Nakit');
        $response->assertSee('Bekleyen Tahsilatlar');
        $response->assertSee('Bekleyen Ödemeler');
        $response->assertSee('Nakit Değişim Oranı');
        $response->assertSee('Nakit Akış Grafiği');
        $response->assertSee('Günlük Nakit Hareketi');
        $response->assertSee('Gelir / Gider Dağılımı');
        $response->assertSee('Bekleyen Tahsilatlar vs Bekleyen Ödemeler');
        $response->assertSee('Nakit Hareketleri');
        $response->assertSee('Bugünkü Hareketler');
        $response->assertSee('Bugünkü Tahsilatlar');
        $response->assertSee('Bugünkü Ödemeler');
        $response->assertSee('nakit hareketi listeleniyor');
        $response->assertSee('cashflow-chart-balance');
    }

    public function test_user_without_financial_permission_cannot_view_cash_flow(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.cash-flow.index'));

        $response->assertForbidden();
    }

    public function test_dummy_data_has_at_least_one_hundred_fifty_transactions(): void
    {
        $analysis = FinanceCashFlowDummyData::analyze(['period' => 'year']);

        $this->assertGreaterThanOrEqual(150, $analysis['total']);
    }

    public function test_running_balance_is_calculated_chronologically(): void
    {
        $analysis = FinanceCashFlowDummyData::analyze(['period' => 'year', 'page' => 1]);
        $chronological = collect($analysis['transactions'])->sortBy('occurred_at')->values();

        if ($chronological->count() < 2) {
            $this->markTestSkipped('Not enough transactions in default page.');
        }

        $first = $chronological->first();
        $this->assertArrayHasKey('balance', $first);
        $this->assertGreaterThan(0, $first['balance']);
    }

    public function test_net_cash_equals_in_minus_out(): void
    {
        $analysis = FinanceCashFlowDummyData::analyze(['period' => 'month']);

        $this->assertEquals(
            round($analysis['kpis']['cash_in'] - $analysis['kpis']['cash_out'], 2),
            $analysis['kpis']['net_cash']
        );
    }

    public function test_cash_flow_can_be_filtered_by_custom_period(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.cash-flow.index', [
            'period' => 'custom',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]));

        $response->assertOk();
        $response->assertSee('nakit hareketi listeleniyor');
    }

    public function test_charts_contain_cash_flow_series(): void
    {
        $analysis = FinanceCashFlowDummyData::analyze(['period' => 'month']);

        $this->assertArrayHasKey('cash_flow', $analysis['charts']);
        $this->assertArrayHasKey('balance', $analysis['charts']['cash_flow']);
        $this->assertArrayHasKey('daily_movement', $analysis['charts']);
        $this->assertArrayHasKey('pending_comparison', $analysis['charts']);
    }
}
