<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceRevenueDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceRevenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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
        $response->assertSee('GLR-2026-000055');
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

        $response = $this->actingAs($user)->get(route('finance.revenues.show', 1));

        $response->assertOk();
        $response->assertSee('Gelir Detayı');
        $response->assertSee('GLR-2026-000001');
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Gelir Bilgileri');
        $response->assertSee('Hakediş Bilgisi');
        $response->assertSee('Fatura Bilgisi');
        $response->assertSee('Tahsilat Bilgisi');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Notlar');
    }

    public function test_dummy_data_has_at_least_fifty_revenue_records(): void
    {
        $revenues = FinanceRevenueDummyData::all();

        $this->assertGreaterThanOrEqual(50, count($revenues));
        $this->assertCount(55, $revenues);
    }

    public function test_revenues_have_mixed_collection_statuses(): void
    {
        $revenues = FinanceRevenueDummyData::all();

        $this->assertGreaterThan(0, collect($revenues)->where('collection_status', 'collected')->count());
        $this->assertGreaterThan(0, collect($revenues)->where('collection_status', 'pending')->count());
        $this->assertGreaterThan(0, collect($revenues)->where('collection_status', 'overdue')->count());
    }

    public function test_revenues_can_be_filtered_by_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.revenues.index', [
            'business_id' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Burger House Gıda Ltd. Şti.');
        $response->assertSee('gelir kaydı listeleniyor');
    }

    public function test_revenues_can_be_filtered_by_collection_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

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
}
