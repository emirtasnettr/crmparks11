<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceCurrentAccountDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCurrentAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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
        $response->assertSee('CAR-000001');
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

    public function test_dummy_data_has_fifty_current_accounts(): void
    {
        $accounts = FinanceCurrentAccountDummyData::all();

        $this->assertCount(50, $accounts);
        $this->assertEquals(20, collect($accounts)->where('type', 'business')->count());
        $this->assertEquals(20, collect($accounts)->where('type', 'courier')->count());
        $this->assertEquals(10, collect($accounts)->where('type', 'agency')->count());
    }

    public function test_each_current_account_has_at_least_ten_movements(): void
    {
        $accounts = FinanceCurrentAccountDummyData::all();

        foreach ($accounts as $account) {
            $this->assertGreaterThanOrEqual(10, count($account['movements']), "Account {$account['code']} has fewer than 10 movements.");
        }
    }

    public function test_current_accounts_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index', [
            'type' => 'courier',
        ]));

        $response->assertOk();
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('CAR-000021');
        $response->assertSee('cari hesap listeleniyor');
    }

    public function test_current_accounts_can_be_filtered_by_balance_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $receivableCount = collect(FinanceCurrentAccountDummyData::filter([
            'search' => '',
            'type' => 'all',
            'status' => 'all',
            'balance_status' => 'receivable',
        ]))->count();

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index', [
            'balance_status' => 'receivable',
        ]));

        $response->assertOk();
        $response->assertSee('cari hesap listeleniyor');
        $this->assertGreaterThan(0, $receivableCount);
    }

    public function test_current_accounts_can_be_searched_by_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.current-accounts.index', [
            'search' => 'CAR-000021',
        ]));

        $response->assertOk();
        $response->assertSee('CAR-000021');
        $response->assertSee('cari hesap listeleniyor');
    }
}
