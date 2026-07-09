<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\Finance\Services\FinanceDashboardService;
use Carbon\Carbon;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceDashboardTest extends TestCase
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

    public function test_finance_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('finance.dashboard.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_with_permission_can_view_finance_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['company_name' => 'Burger House Gıda Ltd. Şti.']);
        FinanceCollection::factory()->for($business)->create();
        FinancePayment::factory()->forCourier()->create();
        CurrentAccountMovement::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('finance.dashboard.index'));

        $response->assertOk();
        $response->assertSee('Finans Dashboard');
        $response->assertSee('Tüm finansal süreçleri tek ekrandan takip edin.');
        $response->assertSee('Toplam Gelir');
        $response->assertSee('Toplam Gider');
        $response->assertSee('Net Kâr');
        $response->assertSee('Kâr Marjı');
        $response->assertSee('Bekleyen Tahsilat');
        $response->assertSee('Bekleyen Ödeme');
        $response->assertSee('Bu Ay Hakediş');
        $response->assertSee('Aktif Cari Hesap');
        $response->assertSee('Aylık Gelir / Gider Grafiği');
        $response->assertSee('Aylık Kâr Analizi');
        $response->assertSee('Gelir Dağılımı');
        $response->assertSee('Gider Dağılımı');
        $response->assertSee('Son Hareketler');
        $response->assertSee('Bekleyen Tahsilatlar');
        $response->assertSee('Bekleyen Ödemeler');
        $response->assertSee('Bugünkü Özet');
        $response->assertSee('Burger House Gıda Ltd. Şti.');
        $response->assertSee('finance-chart-revenue-expense');
    }

    public function test_user_without_financial_permission_cannot_view_finance_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.dashboard.index'));

        $response->assertForbidden();
    }

    public function test_finance_dashboard_can_be_filtered_by_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-09 12:00:00'));

        $user = User::factory()->create();
        $user->assignRole('general_manager');

        FinanceRevenue::factory()->create([
            'amount' => 100_000,
            'revenue_date' => '2026-07-09',
        ]);

        FinanceRevenue::factory()->create([
            'amount' => 500_000,
            'revenue_date' => '2026-07-01',
        ]);

        $monthResponse = $this->actingAs($user)->get(route('finance.dashboard.index', ['period' => 'month']));
        $todayResponse = $this->actingAs($user)->get(route('finance.dashboard.index', ['period' => 'today']));

        $monthResponse->assertOk();
        $todayResponse->assertOk();
        $monthResponse->assertSee('600.000,00 ₺');
        $todayResponse->assertSee('100.000,00 ₺');

        Carbon::setTestNow();
    }

    public function test_finance_dashboard_limits_recent_transactions_to_fifteen(): void
    {
        CurrentAccountMovement::factory()->count(20)->create();

        $transactions = app(FinanceDashboardService::class)->dashboard()['recent_transactions'];

        $this->assertCount(15, $transactions);
    }

    public function test_finance_dashboard_kpis_calculate_profit_margin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

        FinanceRevenue::factory()->create([
            'amount' => 10_000,
            'revenue_date' => '2026-07-10',
        ]);

        FinanceExpense::factory()->create([
            'amount' => 7_500,
            'expense_date' => '2026-07-12',
        ]);

        $kpis = app(FinanceDashboardService::class)->dashboard('month')['kpis'];

        $this->assertEquals(10_000.0, $kpis['total_revenue']);
        $this->assertEquals(7_500.0, $kpis['total_expense']);
        $this->assertEquals(2_500.0, $kpis['net_profit']);
        $this->assertEquals(25.0, $kpis['profit_margin']);

        Carbon::setTestNow();
    }

    public function test_finans_root_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/finans');

        $response->assertRedirect('/finans/dashboard');
    }
}
