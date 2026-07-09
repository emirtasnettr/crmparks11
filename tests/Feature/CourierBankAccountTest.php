<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use App\Modules\Courier\Services\CourierBankAccountPresenter;
use App\Modules\Courier\Services\CourierBankAccountService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierBankAccountTest extends TestCase
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

    public function test_bank_accounts_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.bank-accounts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_bank_accounts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);

        CourierBankAccount::factory()->create([
            'courier_id' => $courier->id,
            'bank_key' => 'ziraat',
            'account_holder' => 'Ahmet Yıldız',
            'iban' => 'TR330006100519786457841326',
            'is_default' => true,
            'status' => 'active',
        ]);

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
        $courier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);

        $account = CourierBankAccount::factory()->create([
            'courier_id' => $courier->id,
            'bank_key' => 'ziraat',
            'account_holder' => 'Ahmet Yıldız',
            'iban' => 'TR330006100519786457841326',
            'notes' => 'Ödemeler bu hesaba yapılır.',
            'is_default' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.bank-accounts.show', $account->id));

        $response->assertOk();
        $response->assertSee('Banka Hesabı Detayı');
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('Banka Bilgileri');
        $response->assertSee('Şube Bilgileri');
        $response->assertSee('Ziraat Bankası');
        $response->assertSee('TR33 **** **** **** **** 1326');
        $response->assertSee('Ödemeler bu hesaba yapılır.');
    }

    public function test_iban_is_masked_correctly(): void
    {
        $masked = CourierBankAccountPresenter::maskIban('TR330006100519786457841326');

        $this->assertEquals('TR33 **** **** **** **** 1326', $masked);
    }

    public function test_each_courier_has_at_most_one_default_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        CourierBankAccount::factory()->create([
            'courier_id' => $courier->id,
            'iban' => 'TR330006100519786457841326',
            'is_default' => true,
        ]);

        $this->actingAs($user)->post(route('couriers.bank-accounts.store'), [
            'courier_id' => $courier->id,
            'bank_key' => 'isbank',
            'account_holder' => 'Ahmet Yıldız',
            'iban' => 'TR640001000902863579985295',
            'is_default' => 1,
            'status' => 'active',
        ])->assertRedirect();

        $this->assertEquals(1, CourierBankAccount::query()
            ->where('courier_id', $courier->id)
            ->where('is_default', true)
            ->count());

        $this->assertEmpty(app(CourierBankAccountService::class)->defaultAccountViolations());
    }

    public function test_bank_accounts_can_be_filtered_by_default_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);

        CourierBankAccount::factory()->create([
            'courier_id' => $courier->id,
            'bank_key' => 'ziraat',
            'account_holder' => 'Ahmet Yıldız',
            'iban' => 'TR330006100519786457841326',
            'is_default' => true,
            'status' => 'active',
        ]);

        CourierBankAccount::factory()->create([
            'courier_id' => $courier->id,
            'bank_key' => 'isbank',
            'account_holder' => 'Ahmet Yıldız',
            'iban' => 'TR640001000902863579985295',
            'is_default' => false,
            'status' => 'inactive',
        ]);

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
        $courier = $this->createCourier($user);

        CourierBankAccount::factory()->create([
            'courier_id' => $courier->id,
            'iban' => 'TR330006100519786457841326',
            'status' => 'active',
        ]);

        CourierBankAccount::factory()->create([
            'courier_id' => $courier->id,
            'iban' => 'TR640001000902863579985295',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.bank-accounts.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertSee('TR64 **** **** **** **** 5295');
        $response->assertDontSee('TR33 **** **** **** **** 1326');
    }

    public function test_courier_bank_account_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('couriers.bank-accounts.store'), [
            'courier_id' => $courier->id,
            'bank_key' => 'garanti',
            'account_holder' => 'Test Kurye',
            'iban' => 'TR320006200519000006289951',
            'is_default' => 1,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('couriers.bank-accounts.index', ['courier_id' => $courier->id]));

        $this->assertDatabaseHas('courier_bank_accounts', [
            'courier_id' => $courier->id,
            'bank_key' => 'garanti',
            'iban' => 'TR320006200519000006289951',
            'is_default' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourier(User $user, array $overrides = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
