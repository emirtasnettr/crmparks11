<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Data\CourierBankAccountDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierBankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_bank_accounts_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.bank-accounts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_bank_accounts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.bank-accounts.index'));

        $response->assertOk();
        $response->assertSee('Banka Bilgileri');
        $response->assertSee('Kuryelere ait banka hesaplarını yönetin.');
        $response->assertSee('Yeni Banka Hesabı');
        $response->assertSee('Toplam Hesap');
        $response->assertSee('Aktif Hesap');
        $response->assertSee('Varsayılan Hesap');
        $response->assertSee('Pasif Hesap');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_authenticated_user_can_view_bank_account_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.bank-accounts.show', 1));

        $response->assertOk();
        $response->assertSee('Banka Hesabı Detayı');
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('Banka Bilgileri');
        $response->assertSee('Şube Bilgileri');
        $response->assertSee('Ziraat Bankası');
        $response->assertSee('TR33 **** **** **** **** 1326');
        $response->assertSee('Ödemeler bu hesaba yapılır.');
        $response->assertDontSee('Hakediş');
    }

    public function test_iban_is_masked_correctly(): void
    {
        $account = CourierBankAccountDummyData::find(1);

        $this->assertNotNull($account);
        $this->assertEquals('TR33 **** **** **** **** 1326', $account['iban_masked']);
    }

    public function test_each_courier_has_at_most_one_default_account(): void
    {
        $violations = CourierBankAccountDummyData::defaultAccountViolations();

        $this->assertEmpty($violations);
    }

    public function test_all_bank_account_records_are_preserved(): void
    {
        $accounts = CourierBankAccountDummyData::all();

        $this->assertCount(40, $accounts);
        $this->assertGreaterThanOrEqual(30, count($accounts));
    }

    public function test_bank_accounts_can_be_filtered_by_default_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.bank-accounts.index', [
            'search' => 'Ahmet',
            'is_default' => 'yes',
        ]));

        $response->assertOk();
        $response->assertSee('TR33 **** **** **** **** 1326');
        $response->assertDontSee('TR64 **** **** **** **** 5295');
    }

    public function test_bank_accounts_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.bank-accounts.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertSee('TR64 **** **** **** **** 5295');
        $response->assertDontSee('TR33 **** **** **** **** 1326');
    }
}
