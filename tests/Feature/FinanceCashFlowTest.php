<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\Finance\Services\CashFlowService;
use Carbon\Carbon;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCashFlowTest extends TestCase
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

    public function test_cash_flow_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.cash-flow.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cash_flow_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceCollection::factory()->collected()->create();
        FinancePayment::factory()->paid()->create();
        FinanceRevenue::factory()->create();
        FinanceExpense::factory()->create();

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

    public function test_cash_flow_lists_all_transaction_sources(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-09 12:00:00'));

        FinanceCollection::factory()->collected()->create();
        FinancePayment::factory()->paid()->create();
        FinanceRevenue::factory()->create(['revenue_date' => '2026-07-09']);
        FinanceExpense::factory()->create(['expense_date' => '2026-07-09']);

        $analysis = app(CashFlowService::class)->analyze(['period' => 'month']);

        $this->assertGreaterThanOrEqual(4, $analysis['total']);

        Carbon::setTestNow();
    }

    public function test_running_balance_is_calculated_chronologically(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-09 12:00:00'));

        FinanceRevenue::factory()->create([
            'amount' => 50_000,
            'revenue_date' => '2026-07-01',
        ]);

        FinanceExpense::factory()->create([
            'amount' => 20_000,
            'expense_date' => '2026-07-05',
        ]);

        $analysis = app(CashFlowService::class)->analyze(['period' => 'month', 'page' => 1]);
        $chronological = collect($analysis['transactions'])->sortBy('occurred_at')->values();

        $this->assertGreaterThanOrEqual(2, $chronological->count());

        $first = $chronological->first();
        $this->assertArrayHasKey('balance', $first);
        $this->assertEquals(50_000.0, $first['balance']);

        Carbon::setTestNow();
    }

    public function test_net_cash_equals_in_minus_out(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-09 12:00:00'));

        FinanceRevenue::factory()->create([
            'amount' => 100_000,
            'revenue_date' => '2026-07-09',
        ]);

        FinanceExpense::factory()->create([
            'amount' => 35_000,
            'expense_date' => '2026-07-09',
        ]);

        $analysis = app(CashFlowService::class)->analyze(['period' => 'month']);

        $this->assertEquals(
            round($analysis['kpis']['cash_in'] - $analysis['kpis']['cash_out'], 2),
            $analysis['kpis']['net_cash']
        );

        Carbon::setTestNow();
    }

    public function test_cash_flow_can_be_filtered_by_custom_period(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceRevenue::factory()->create(['revenue_date' => '2026-06-15']);

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
        Carbon::setTestNow(Carbon::parse('2026-07-09 12:00:00'));

        FinanceRevenue::factory()->create(['revenue_date' => '2026-07-09']);
        FinanceExpense::factory()->create(['expense_date' => '2026-07-09']);

        $analysis = app(CashFlowService::class)->analyze(['period' => 'month']);

        $this->assertArrayHasKey('cash_flow', $analysis['charts']);
        $this->assertArrayHasKey('balance', $analysis['charts']['cash_flow']);
        $this->assertArrayHasKey('daily_movement', $analysis['charts']);
        $this->assertArrayHasKey('pending_comparison', $analysis['charts']);

        Carbon::setTestNow();
    }
}
