<?php

namespace Tests\Feature;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Services\ProfitabilityService;
use Carbon\Carbon;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceProfitabilityTest extends TestCase
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

    public function test_profitability_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.profitability.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profitability_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        EarningLine::factory()->create();

        $response = $this->actingAs($user)->get(route('finance.profitability.index'));

        $response->assertOk();
        $response->assertSee('Karlılık Analizi');
        $response->assertSee('Toplam Gelir');
        $response->assertSee('Toplam Gider');
        $response->assertSee('Net Kâr');
        $response->assertSee('Kâr Marjı');
        $response->assertSee('Paket Başına Ort. Kâr');
        $response->assertSee('En Karlı İşletme');
        $response->assertSee('En Karlı Acente');
        $response->assertSee('En Karlı Kurye Operasyonu');
        $response->assertSee('Gelir / Gider / Kâr');
        $response->assertSee('İşletme Bazlı Kârlılık');
        $response->assertSee('Acente Bazlı Kârlılık');
        $response->assertSee('İl Bazlı Kârlılık');
        $response->assertSee('Gelir Dağılımı');
        $response->assertSee('İşletme Karlılık Tablosu');
        $response->assertSee('Acente Karlılık Tablosu');
        $response->assertSee('Kurye Maliyet Tablosu');
        $response->assertSee('En Karlı İşletmeler');
        $response->assertSee('En Düşük Karlı İşletmeler');
        $response->assertSee('profitability-chart-trend');
    }

    public function test_user_without_financial_permission_cannot_view_profitability(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->get(route('finance.profitability.index'));

        $response->assertForbidden();
    }

    public function test_net_profit_calculation_is_correct(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

        EarningLine::factory()->create([
            'period_month' => 7,
            'period_year' => 2026,
            'revenue_total' => 100_000,
            'courier_total' => 60_000,
            'net_courier_payment' => 60_000,
            'agency_payment' => 10_000,
            'extra_expense' => 5_000,
            'package_count' => 1000,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
        ]);

        $analysis = app(ProfitabilityService::class)->analyze(['date_range' => 'month']);

        $businessTotal = collect($analysis['business_table'])->sum('net_profit');
        $this->assertEquals(round($businessTotal, 2), $analysis['kpis']['net_profit']);

        Carbon::setTestNow();
    }

    public function test_profit_margin_formula_is_applied(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

        EarningLine::factory()->create([
            'period_month' => 7,
            'period_year' => 2026,
            'revenue_total' => 100_000,
            'courier_total' => 70_000,
            'net_courier_payment' => 70_000,
            'agency_payment' => 0,
            'extra_expense' => 0,
            'package_count' => 500,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
        ]);

        $analysis = app(ProfitabilityService::class)->analyze(['date_range' => 'month']);
        $row = $analysis['business_table'][0];

        $expectedMargin = $row['revenue'] > 0
            ? round(($row['net_profit'] / $row['revenue']) * 100, 1)
            : 0;

        $this->assertEquals($expectedMargin, $row['profit_margin']);

        Carbon::setTestNow();
    }

    public function test_profitability_can_be_filtered_by_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['company_name' => 'Burger House Gıda Ltd. Şti.']);
        EarningLine::factory()->for($business)->create([
            'period_month' => 7,
            'period_year' => 2026,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
        ]);

        $response = $this->actingAs($user)->get(route('finance.profitability.index', [
            'business_id' => $business->id,
        ]));

        $response->assertOk();
        $response->assertSee('Burger House Gıda Ltd. Şti.');
    }

    public function test_charts_data_contains_trend_and_distributions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

        EarningLine::factory()->count(2)->create([
            'period_month' => 7,
            'period_year' => 2026,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
        ]);

        $analysis = app(ProfitabilityService::class)->analyze(['date_range' => 'month']);

        $this->assertArrayHasKey('trend', $analysis['charts']);
        $this->assertArrayHasKey('revenue', $analysis['charts']['trend']);
        $this->assertArrayHasKey('expense', $analysis['charts']['trend']);
        $this->assertArrayHasKey('profit', $analysis['charts']['trend']);
        $this->assertNotEmpty($analysis['charts']['business_profitability']);
        $this->assertNotEmpty($analysis['charts']['revenue_distribution']);

        Carbon::setTestNow();
    }
}
