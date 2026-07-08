<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceDashboardDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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
        $user = User::factory()->create();
        $user->assignRole('general_manager');

        $monthResponse = $this->actingAs($user)->get(route('finance.dashboard.index', ['period' => 'month']));
        $todayResponse = $this->actingAs($user)->get(route('finance.dashboard.index', ['period' => 'today']));

        $monthResponse->assertOk();
        $todayResponse->assertOk();
        $monthResponse->assertSee('8.450.000,00 ₺');
        $todayResponse->assertSee('287.300,00 ₺');
    }

    public function test_finance_dashboard_dummy_data_has_fifteen_recent_transactions(): void
    {
        $transactions = FinanceDashboardDummyData::recentTransactions();

        $this->assertCount(15, $transactions);
    }

    public function test_finance_dashboard_kpis_calculate_profit_margin(): void
    {
        $kpis = FinanceDashboardDummyData::kpis('month');

        $this->assertEquals(8_450_000, $kpis['total_revenue']);
        $this->assertEquals(6_120_000, $kpis['total_expense']);
        $this->assertEquals(2_330_000, $kpis['net_profit']);
        $this->assertEquals(27.6, $kpis['profit_margin']);
    }

    public function test_finans_root_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/finans');

        $response->assertRedirect('/finans/dashboard');
    }
}
