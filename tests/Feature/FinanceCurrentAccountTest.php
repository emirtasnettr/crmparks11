<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Services\CurrentAccountService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCurrentAccountTest extends TestCase
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

    public function test_current_accounts_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.current-accounts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_current_accounts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['company_name' => 'Burger House Gıda Ltd. Şti.']);
        $courier = Courier::factory()->create(['full_name' => 'Ahmet Yıldız']);
        $agency = Agency::factory()->create(['company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.']);

        app(CurrentAccountService::class)->ensureForEntity($business);
        app(CurrentAccountService::class)->ensureForEntity($courier);
        app(CurrentAccountService::class)->ensureForEntity($agency);

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index'));

        $response->assertOk();
        $response->assertSee('Cari Hesaplar');
        $response->assertSee('Sistemdeki tüm cari hesapları yönetin.');
        $response->assertSee('Yeni Cari Hesap');
        $response->assertSee('Yeni Hareket');
        $response->assertSee('Toplam Cari');
        $response->assertSee('Toplam Alacak');
        $response->assertSee('Toplam Borç');
        $response->assertSee('Net Bakiye');
        $response->assertSee('Vadesi Geçen Alacak');
        $response->assertSee('Vadesi Geçen Borç');
        $response->assertSee('Burger House Gıda Ltd. Şti.');
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
    }

    public function test_user_without_financial_permission_cannot_view_current_accounts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index'));

        $response->assertForbidden();
    }

    public function test_entity_accounts_are_synced_on_index(): void
    {
        Business::factory()->count(2)->create();
        Courier::factory()->count(2)->create();
        Agency::factory()->count(1)->create();

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get(route('finance.current-accounts.index'));

        $this->assertDatabaseCount('current_accounts', 5);
    }

    public function test_current_accounts_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create(['full_name' => 'Ahmet Yıldız']);
        $account = app(CurrentAccountService::class)->ensureForEntity($courier);

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index', [
            'type' => 'courier',
        ]));

        $response->assertOk();
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee($account->code);
        $response->assertSee('cari hesap listeleniyor');
    }

    public function test_current_accounts_can_be_filtered_by_balance_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create();
        $account = app(CurrentAccountService::class)->ensureForEntity($business);

        CurrentAccountMovement::factory()->for($account)->create([
            'type' => 'invoice',
            'debit' => 10000,
            'credit' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index', [
            'balance_status' => 'receivable',
        ]));

        $response->assertOk();
        $response->assertSee('cari hesap listeleniyor');
        $response->assertSee($account->code);
    }

    public function test_current_accounts_can_be_searched_by_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create(['full_name' => 'Test Kurye']);
        $account = app(CurrentAccountService::class)->ensureForEntity($courier);

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index', [
            'search' => $account->code,
        ]));

        $response->assertOk();
        $response->assertSee($account->code);
        $response->assertSee('cari hesap listeleniyor');
    }

    public function test_user_can_create_manual_current_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->post(route('finance.current-accounts.store'), [
            'type' => 'business',
            'title' => 'Manuel Cari Ltd. Şti.',
            'phone' => '0212 111 22 33',
            'email' => 'muhasebe@manuel.test',
            'tax_number' => '1234567890',
        ]);

        $response->assertRedirect(route('finance.current-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('current_accounts', [
            'title' => 'Manuel Cari Ltd. Şti.',
            'account_type' => 'business',
        ]);
    }

    public function test_user_can_create_current_account_movement(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $account = CurrentAccount::factory()->business()->create([
            'title' => 'Hareket Test İşletmesi',
        ]);

        $response = $this->actingAs($user)->post(route('finance.current-accounts.movements.store'), [
            'current_account_id' => $account->id,
            'transaction_date' => now()->toDateString(),
            'type' => 'collection',
            'document_no' => 'THS-2026-0001',
            'amount' => 5000,
            'description' => 'Test tahsilat',
        ]);

        $response->assertRedirect(route('finance.current-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('current_account_movements', [
            'current_account_id' => $account->id,
            'type' => 'collection',
            'credit' => 5000,
            'debit' => 0,
        ]);
    }
}
