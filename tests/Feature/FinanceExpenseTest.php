<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceExpenseDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_expenses_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.expenses.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_expenses_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.expenses.index'));

        $response->assertOk();
        $response->assertSee('Giderler');
        $response->assertSee('Şirkete ait tüm giderleri buradan yönetin.');
        $response->assertSee('Yeni Gider');
        $response->assertSee('PDF Raporu');
        $response->assertSee('Toplam Gider');
        $response->assertSee('Bu Ay Gideri');
        $response->assertSee('Ödenen Gider');
        $response->assertSee('Bekleyen Ödeme');
        $response->assertSee('Kurye Gideri');
        $response->assertSee('Acente Gideri');
        $response->assertSee('GDR-2026-000070');
        $response->assertSee('Kaynak: Manuel');
        $response->assertSee('Personel');
    }

    public function test_user_without_financial_permission_cannot_view_expenses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.expenses.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_expense_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.expenses.show', 1));

        $response->assertOk();
        $response->assertSee('Gider Detayı');
        $response->assertSee('GDR-2026-000001');
        $response->assertSee('Gider Bilgileri');
        $response->assertSee('Kurye / Acente Bilgileri');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Ödeme Bilgisi');
        $response->assertSee('Belgeler');
        $response->assertSee('Notlar');
        $response->assertSee('Kaynak: Hakediş');
    }

    public function test_dummy_data_has_seventy_expense_records_with_expected_distribution(): void
    {
        $expenses = FinanceExpenseDummyData::all();

        $this->assertCount(70, $expenses);
        $this->assertEquals(25, collect($expenses)->where('expense_type', 'courier_earning')->count());
        $this->assertEquals(15, collect($expenses)->where('expense_type', 'agency_earning')->count());
        $this->assertEquals(10, collect($expenses)->where('expense_type', 'personnel')->count());
        $this->assertEquals(5, collect($expenses)->where('expense_type', 'fuel')->count());
        $this->assertEquals(5, collect($expenses)->where('expense_type', 'software')->count());
        $this->assertEquals(5, collect($expenses)->where('expense_type', 'advertising')->count());
    }

    public function test_expenses_have_mixed_payment_statuses(): void
    {
        $expenses = FinanceExpenseDummyData::all();

        $this->assertGreaterThan(0, collect($expenses)->where('payment_status', 'paid')->count());
        $this->assertGreaterThan(0, collect($expenses)->where('payment_status', 'pending')->count());
        $this->assertGreaterThan(0, collect($expenses)->where('payment_status', 'overdue')->count());
    }

    public function test_expenses_can_be_filtered_by_courier(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.expenses.index', [
            'courier_id' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('gider kaydı listeleniyor');
    }

    public function test_expenses_can_be_filtered_by_expense_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.expenses.index', [
            'expense_type' => 'agency_earning',
        ]));

        $response->assertOk();
        $response->assertSee('Acente Hakedişi');
        $response->assertSee('gider kaydı listeleniyor');
    }

    public function test_expense_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.expenses.show', 9999));

        $response->assertNotFound();
    }
}
