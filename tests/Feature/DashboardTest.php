<?php

namespace Tests\Feature;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard_with_live_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Business::factory()->count(3)->create(['created_by' => $user->id]);
        Courier::factory()->count(4)->create(['created_by' => $user->id]);
        Agency::factory()->count(2)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee(number_format(Business::query()->count()));
        $response->assertSee(number_format(Courier::query()->count()));
        $response->assertSee(number_format(Agency::query()->count()));
        $response->assertSee('Açılış Aşamasındakiler');
        $response->assertSee('Son Eklenen İşletmeler');
        $response->assertSee('Son Eklenen Kuryeler');
        $response->assertSee('Kurye Tür Dağılımı');
        $response->assertDontSee('Finans Özeti');
        $response->assertDontSee('Bu Ay Gelir');
        $response->assertDontSee('Bekleyen Tahsilatlar');
        $response->assertDontSee('Bekleyen Ödemeler');
        $response->assertDontSee('Onay Bekleyen Hakedişler');
    }

    public function test_opening_stage_businesses_appear_on_dashboard_sorted_by_start_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $later = Business::factory()->create([
            'brand_name' => 'Geç Açılacak',
            'status' => 'opening_stage',
            'start_date' => now()->addDays(5)->toDateString(),
            'planned_courier_count' => 8,
            'created_by' => $user->id,
        ]);

        $soon = Business::factory()->create([
            'brand_name' => 'Yarın Açılacak',
            'status' => 'opening_stage',
            'start_date' => now()->addDay()->toDateString(),
            'planned_courier_count' => 4,
            'created_by' => $user->id,
        ]);

        Business::factory()->create([
            'brand_name' => 'Aktif Marka',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $rows = app(DashboardService::class)->getOpeningStageBusinesses();

        $this->assertCount(2, $rows);
        $this->assertSame($soon->id, $rows[0]['id']);
        $this->assertSame($later->id, $rows[1]['id']);
        $this->assertTrue($rows[0]['is_opening_soon']);
        $this->assertFalse($rows[1]['is_opening_soon']);
        $this->assertFalse($rows[0]['is_opening_overdue']);
        $this->assertFalse($rows[1]['is_opening_overdue']);
        $this->assertSame(4, $rows[0]['planned_courier_count']);

        $overdue = Business::factory()->create([
            'brand_name' => 'Gecikmiş Açılış',
            'status' => 'opening_stage',
            'start_date' => now()->subDays(3)->toDateString(),
            'planned_courier_count' => 2,
            'created_by' => $user->id,
        ]);

        $rowsWithOverdue = collect(app(DashboardService::class)->getOpeningStageBusinesses())
            ->firstWhere('id', $overdue->id);

        $this->assertNotNull($rowsWithOverdue);
        $this->assertTrue($rowsWithOverdue['is_opening_overdue']);
        $this->assertFalse($rowsWithOverdue['is_opening_soon']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Yarın Açılacak');
        $response->assertSee('Geç Açılacak');
        $response->assertSee('1 gün kaldı');
        $response->assertSee('Gecikmiş Açılış');
        $response->assertSee('3 gün gecikti');
        $response->assertSee('opening-overdue-blink', false);
        $response->assertSee('Açılış Aşamasındakiler');
    }

    public function test_operations_specialist_does_not_see_finance_overview(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Finans Özeti');
        $response->assertDontSee('Bekleyen Tahsilatlar');
        $response->assertDontSee('Bekleyen Ödemeler');
        $response->assertDontSee('Onay Bekleyen Hakedişler');
    }

    public function test_sales_manager_sees_sales_dashboard_not_courier_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales_manager');

        Business::factory()->create([
            'brand_name' => 'Aktif Satış Markası',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        Business::factory()->create([
            'brand_name' => 'Sözleşme Bekleyen',
            'status' => 'contract_stage',
            'created_by' => $user->id,
        ]);

        Business::factory()->create([
            'brand_name' => 'Açılış Yakın',
            'status' => 'opening_stage',
            'start_date' => now()->addDays(3)->toDateString(),
            'created_by' => $user->id,
        ]);

        Courier::factory()->create([
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        Courier::factory()->create([
            'status' => 'inactive',
            'created_by' => $user->id,
        ]);

        $contractBusiness = Business::factory()->create([
            'brand_name' => 'Sözleşmesi Bitiyor',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        \App\Models\Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $contractBusiness->id,
            'title' => 'Yakın Bitiş Sözleşmesi',
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $form = \App\Modules\FormBuilder\Models\Form::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Satış Lead Formu',
            'slug' => 'satis-lead-formu',
            'status' => 'active',
            'fields' => [],
        ]);

        $status = \App\Modules\FormBuilder\Models\FormSubmissionStatus::query()->firstOrCreate(
            ['slug' => 'yeni-basvuru'],
            ['name' => 'Yeni Başvuru', 'color' => 'primary', 'sort_order' => 1, 'is_default' => true],
        );

        \App\Modules\FormBuilder\Models\FormSubmission::query()->create([
            'form_id' => $form->id,
            'form_submission_status_id' => $status->id,
            'landing_page_slug' => 'lead',
            'landing_page_name' => 'Lead',
            'data' => ['ad' => 'Aday'],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('İşte satış özetiniz.');
        $response->assertSee('Açılış Aşamasındakiler');
        $response->assertSee('Açılış Yakın');
        $response->assertSee('Aktif İşletme');
        $response->assertSee('Sözleşme Aşamasında');
        $response->assertSee('Bu Ay Yeni');
        $response->assertSee('Aktif Kurye');
        $response->assertSee('İşletme Pipeline');
        $response->assertSee('Son Eklenen İşletmeler');
        $response->assertSee('Yakında Bitecek Sözleşmeler');
        $response->assertSee('Sözleşmesi Bitiyor');
        $response->assertSee('Son Form Başvuruları');
        $response->assertSee('Satış Lead Formu');
        $response->assertDontSee('Son Eklenen Kuryeler');
        $response->assertDontSee('Kurye Tür Dağılımı');
        $response->assertDontSee('Toplam Kurye');
        $response->assertDontSee('İşte operasyon özetiniz.');
    }

    public function test_dashboard_service_returns_sales_stats_pipeline_and_lists(): void
    {
        $user = User::factory()->create();

        Business::factory()->create(['status' => 'active', 'created_by' => $user->id]);
        Business::factory()->create(['status' => 'contract_stage', 'created_by' => $user->id]);
        Business::factory()->create([
            'status' => 'inactive',
            'created_by' => $user->id,
            'created_at' => now()->subMonths(2),
        ]);

        Courier::factory()->count(2)->create(['status' => 'active', 'created_by' => $user->id]);
        Courier::factory()->create(['status' => 'inactive', 'created_by' => $user->id]);

        $business = Business::factory()->create(['status' => 'pending', 'created_by' => $user->id]);
        \App\Models\Contract::factory()->create([
            'contractable_id' => $business->id,
            'end_date' => now()->addDays(5)->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = app(DashboardService::class);
        $stats = $service->getSalesStats();
        $pipeline = $service->getBusinessStatusDistribution();
        $contracts = $service->getExpiringContracts();

        $this->assertSame(4, $stats['total_businesses']);
        $this->assertSame(1, $stats['active_businesses']);
        $this->assertSame(1, $stats['contract_stage_businesses']);
        $this->assertSame(2, $stats['active_couriers']);
        $this->assertSame(Business::query()->where('created_at', '>=', now()->startOfMonth())->count(), $stats['businesses_added_this_month']);
        $this->assertSame(4, $pipeline['total']);
        $this->assertNotEmpty($pipeline['items']);
        $this->assertCount(1, $contracts);
    }

    public function test_dashboard_service_returns_latest_entities_and_distribution(): void
    {
        $user = User::factory()->create();
        Business::factory()->count(6)->create(['created_by' => $user->id]);
        Courier::factory()->count(6)->create(['created_by' => $user->id]);

        $service = app(DashboardService::class);

        $latestBusinesses = $service->getLatestBusinesses();
        $latestCouriers = $service->getLatestCouriers();
        $distribution = $service->getCourierTypeDistribution();

        $this->assertCount(5, $latestBusinesses);
        $this->assertCount(5, $latestCouriers);
        $this->assertSame(Business::query()->max('id'), $latestBusinesses[0]['id']);
        $this->assertSame(Courier::query()->max('id'), $latestCouriers[0]['id']);
        $this->assertSame(Courier::query()->count(), $distribution['total']);
        $this->assertCount(2, $distribution['items']);
    }

    public function test_dashboard_service_aggregates_live_business_count(): void
    {
        $user = User::factory()->create();
        Business::factory()->count(2)->create(['created_by' => $user->id]);
        Courier::factory()->count(3)->create(['created_by' => $user->id, 'status' => 'active']);
        Courier::factory()->count(1)->create(['created_by' => $user->id, 'status' => 'inactive']);
        Agency::factory()->count(2)->create(['created_by' => $user->id]);

        $stats = app(DashboardService::class)->getStats();

        $this->assertSame(Business::query()->count(), $stats['total_businesses']);
        $this->assertSame(Courier::query()->count(), $stats['total_couriers']);
        $this->assertSame(Agency::query()->count(), $stats['total_agencies']);
        $this->assertSame(Courier::query()->where('status', 'active')->count(), $stats['active_couriers']);
        $this->assertArrayNotHasKey('inactive_couriers', $stats);
        $this->assertArrayNotHasKey('monthly_revenue', $stats);
    }

    public function test_dashboard_service_returns_finance_overview_and_pending_lists(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['created_by' => $user->id]);
        $courier = Courier::factory()->create(['created_by' => $user->id]);

        FinanceRevenue::factory()->create([
            'business_id' => $business->id,
            'amount' => 10000,
            'revenue_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        FinanceExpense::factory()->create([
            'amount' => 2500,
            'expense_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        FinanceCollection::factory()->create([
            'business_id' => $business->id,
            'total_amount' => 5000,
            'collected_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(3)->toDateString(),
            'created_by' => $user->id,
        ]);

        FinancePayment::factory()->create([
            'recipient_type' => 'courier',
            'courier_id' => $courier->id,
            'recipient_name' => $courier->full_name,
            'total_amount' => 3000,
            'paid_amount' => 0,
            'status' => 'pending',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'created_by' => $user->id,
        ]);

        $pendingStatusId = EarningStatus::query()->where('code', 'pending_review')->value('id');
        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'status_id' => $pendingStatusId,
            'created_by' => $user->id,
        ]);

        $service = app(DashboardService::class);
        $finance = $service->getFinanceOverview();

        $this->assertSame(10000.0, $finance['revenue']);
        $this->assertSame(2500.0, $finance['expense']);
        $this->assertSame(7500.0, $finance['net_profit']);
        $this->assertSame(5000.0, $finance['pending_collection']);
        $this->assertSame(3000.0, $finance['pending_payment']);
        $this->assertSame(1, $finance['pending_earning_count']);
        $this->assertCount(1, $service->getPendingCollections());
        $this->assertCount(1, $service->getPendingPayments());
        $this->assertCount(1, $service->getPendingEarnings());
    }
}
