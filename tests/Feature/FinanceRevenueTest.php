<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceRevenue;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceRevenueTest extends TestCase
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

    public function test_revenues_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.revenues.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_revenues_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'company_name' => 'Burger House Gıda Ltd. Şti.',
            'brand_name' => 'Burger House',
        ]);

        $revenue = FinanceRevenue::factory()->for($business)->create([
            'revenue_type' => 'per_package',
            'amount' => 25000,
        ]);

        $response = $this->actingAs($user)->get(route('finance.revenues.index'));

        $response->assertOk();
        $response->assertSee('Gelirler');
        $response->assertSee('İşletmelerden elde edilen tüm gelir kayıtlarını yönetin.');
        $response->assertSee('Yeni Gelir');
        $response->assertSee('PDF Raporu');
        $response->assertSee('Toplam Gelir');
        $response->assertSee('Bu Ay Geliri');
        $response->assertSee('Tahsil Edilen');
        $response->assertSee('Bekleyen Tahsilat');
        $response->assertSee('Ortalama İşletme Geliri');
        $response->assertSee($revenue->reference);
        $response->assertSee('Burger House Gıda Ltd. Şti.');
        $response->assertSee('Paket Başı Hizmet');
    }

    public function test_user_without_financial_permission_cannot_view_revenues(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.revenues.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_revenue_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create();
        $revenue = FinanceRevenue::factory()
            ->for($business)
            ->withInvoice('FTR-2026-0100')
            ->create([
                'notes' => 'Muhasebe onayı tamamlandı.',
            ]);

        $response = $this->actingAs($user)->get(route('finance.revenues.show', $revenue->id));

        $response->assertOk();
        $response->assertSee('Gelir Detayı');
        $response->assertSee($revenue->reference);
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Gelir Bilgileri');
        $response->assertSee('Fatura Bilgisi');
        $response->assertSee('Tahsilat Bilgisi');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Muhasebe onayı tamamlandı.');
    }

    public function test_revenues_can_be_filtered_by_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['company_name' => 'Burger House Gıda Ltd. Şti.']);
        FinanceRevenue::factory()->for($business)->create();
        FinanceRevenue::factory()->create();

        $response = $this->actingAs($user)->get(route('finance.revenues.index', [
            'business_id' => $business->id,
        ]));

        $response->assertOk();
        $response->assertSee('Burger House Gıda Ltd. Şti.');
        $response->assertSee('gelir kaydı listeleniyor');
    }

    public function test_revenues_can_be_filtered_by_collection_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceRevenue::factory()->overdue()->create();
        FinanceRevenue::factory()->collected()->create();

        $response = $this->actingAs($user)->get(route('finance.revenues.index', [
            'collection_status' => 'overdue',
        ]));

        $response->assertOk();
        $response->assertSee('Gecikmiş');
        $response->assertSee('gelir kaydı listeleniyor');
    }

    public function test_revenue_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.revenues.show', 9999));

        $response->assertNotFound();
    }

    public function test_user_can_create_revenue(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'company_name' => 'Test İşletme A.Ş.',
            'brand_name' => 'Test Marka',
        ]);

        $response = $this->actingAs($user)->post(route('finance.revenues.store'), [
            'business_id' => $business->id,
            'revenue_type' => 'manual',
            'period_label' => 'Temmuz 2026',
            'invoice_no' => 'FTR-2026-0099',
            'revenue_date' => '2026-07-09',
            'amount' => 12500,
            'vat_rate' => 20,
            'description' => 'Manuel test geliri',
            'collection_status' => 'pending',
        ]);

        $response->assertRedirect(route('finance.revenues.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_revenues', [
            'business_id' => $business->id,
            'revenue_type' => 'manual',
            'amount' => 12500,
            'invoice_no' => 'FTR-2026-0099',
            'collection_status' => 'pending',
        ]);

        $this->assertDatabaseHas('current_accounts', [
            'accountable_type' => Business::class,
            'accountable_id' => $business->id,
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'type' => 'invoice',
            'debit' => 12500,
        ]);
    }
}
