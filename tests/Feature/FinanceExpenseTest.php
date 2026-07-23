<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceExpense;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceExpenseTest extends TestCase
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

    public function test_expenses_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.expenses.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_expenses_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create(['full_name' => 'Ahmet Yıldız']);
        $expense = FinanceExpense::factory()
            ->courierEarning()
            ->for($courier)
            ->create([
                'amount' => 15000,
                'description' => 'Kurye hakediş ödemesi',
            ]);

        FinanceExpense::factory()->create([
            'expense_type' => 'personnel',
            'source' => 'manual',
            'description' => 'Personel maaş ödemesi',
        ]);

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
        $response->assertSee($expense->reference);
        $response->assertSee('Kaynak: Manuel');
        $response->assertSee('Personel');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_user_without_financial_permission_cannot_view_expenses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->get(route('finance.expenses.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_expense_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create();
        $expense = FinanceExpense::factory()
            ->courierEarning()
            ->for($courier)
            ->paid()
            ->create([
                'notes' => 'Muhasebe onayı tamamlandı.',
            ]);

        $response = $this->actingAs($user)->get(route('finance.expenses.show', $expense->id));

        $response->assertOk();
        $response->assertSee('Gider Detayı');
        $response->assertSee($expense->reference);
        $response->assertSee('Gider Bilgileri');
        $response->assertSee('Kurye / Acente Bilgileri');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Ödeme Bilgisi');
        $response->assertSee('Belgeler');
        $response->assertSee('Notlar');
        $response->assertSee('Kaynak: Hakediş');
        $response->assertSee('Muhasebe onayı tamamlandı.');
    }

    public function test_expenses_can_be_filtered_by_courier(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create(['full_name' => 'Ahmet Yıldız']);
        FinanceExpense::factory()->courierEarning()->for($courier)->create();
        FinanceExpense::factory()->agencyEarning()->create();

        $response = $this->actingAs($user)->get(route('finance.expenses.index', [
            'courier_id' => $courier->id,
        ]));

        $response->assertOk();
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('gider kaydı listeleniyor');
    }

    public function test_expenses_can_be_filtered_by_expense_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceExpense::factory()->agencyEarning()->create();
        FinanceExpense::factory()->create(['expense_type' => 'personnel']);

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

    public function test_user_can_create_manual_expense(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->post(route('finance.expenses.store'), [
            'expense_type' => 'personnel',
            'expense_date' => '2026-07-09',
            'amount' => 8500,
            'vat_rate' => 20,
            'description' => 'Personel maaş ödemesi',
            'payment_status' => 'pending',
            'document_no' => 'BLG-2026-0001',
        ]);

        $response->assertRedirect(route('finance.expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_expenses', [
            'expense_type' => 'personnel',
            'source' => 'manual',
            'amount' => 8500,
            'payment_status' => 'pending',
            'document_no' => 'BLG-2026-0001',
        ]);
    }

    public function test_user_can_create_courier_expense_with_current_account_movement(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create([
            'full_name' => 'Test Kurye',
        ]);

        $response = $this->actingAs($user)->post(route('finance.expenses.store'), [
            'expense_type' => 'courier_earning',
            'courier_id' => $courier->id,
            'expense_date' => '2026-07-09',
            'amount' => 12000,
            'vat_rate' => 20,
            'description' => 'Kurye hakediş ödemesi',
            'payment_status' => 'paid',
        ]);

        $response->assertRedirect(route('finance.expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_expenses', [
            'expense_type' => 'courier_earning',
            'source' => 'earning',
            'courier_id' => $courier->id,
            'amount' => 12000,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('current_accounts', [
            'accountable_type' => Courier::class,
            'accountable_id' => $courier->id,
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'type' => 'payment',
            'debit' => 12000,
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'type' => 'credit_note',
            'credit' => 12000,
        ]);
    }

    public function test_user_can_create_agency_expense_with_debit_note_when_pending(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $agency = Agency::factory()->create([
            'company_name' => 'Test Acente Ltd.',
        ]);

        $response = $this->actingAs($user)->post(route('finance.expenses.store'), [
            'expense_type' => 'agency_earning',
            'agency_id' => $agency->id,
            'expense_date' => '2026-07-09',
            'amount' => 9500,
            'payment_status' => 'pending',
        ]);

        $response->assertRedirect(route('finance.expenses.index'));

        $this->assertDatabaseHas('finance_expenses', [
            'expense_type' => 'agency_earning',
            'agency_id' => $agency->id,
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'type' => 'credit_note',
            'credit' => 9500,
        ]);
    }
}
