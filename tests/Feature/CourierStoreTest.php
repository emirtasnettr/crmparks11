<?php

namespace Tests\Feature;

use App\Core\Enums\UserType;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Services\CourierUserProvisioner;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierStoreTest extends TestCase
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

    public function test_courier_store_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('couriers.store'), [
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'phone' => '0532 111 22 33',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-01-15',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_courier_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->post(route('couriers.store'), [
            'first_name' => 'Point',
            'last_name' => 'Kurye',
            'phone' => '0532 444 55 66',
            'email' => 'point@kurye.test',
            'tc_number' => '12345678901',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'address' => 'Test Mahallesi',
            'start_date' => '2024-06-01',
            'status' => 'active',
            'notes' => 'Canlı kayıt testi',
        ]);

        $courier = Courier::query()->first();

        $this->assertNotNull($courier);
        $response->assertRedirect(route('couriers.show', $courier->id));
        $response->assertSessionHas('success', 'Kurye başarıyla oluşturuldu.');

        $this->assertSame('Point', $courier->first_name);
        $this->assertSame('Kurye', $courier->last_name);
        $this->assertSame('Point Kurye', $courier->full_name);
        $this->assertSame('12345678901', $courier->tc_number);
        $this->assertSame('Canlı kayıt testi', $courier->notes);

        $indexResponse = $this->actingAs($user)->get(route('couriers.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Point Kurye');

        $courierUser = User::query()->findOrFail($courier->user_id);
        $this->assertSame('point@kurye.test', $courierUser->email);
        $this->assertSame(UserType::Courier, $courierUser->user_type);
        $this->assertTrue($courierUser->hasRole('courier'));
        $this->assertTrue(Hash::check(CourierUserProvisioner::DEFAULT_PASSWORD, $courierUser->password));
        $this->assertSame(Courier::class, $courierUser->profileable_type);
        $this->assertSame($courier->id, $courierUser->profileable_id);
    }

    public function test_courier_user_is_created_with_generated_email_when_missing(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->post(route('couriers.store'), [
            'first_name' => 'Otomatik',
            'last_name' => 'Kurye',
            'phone' => '0532 777 88 99',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-06-01',
            'status' => 'active',
        ])->assertRedirect();

        $courier = Courier::query()->where('full_name', 'Otomatik Kurye')->firstOrFail();
        $courierUser = User::query()->findOrFail($courier->user_id);

        $this->assertStringEndsWith('@crmlog.com', $courierUser->email);
        $this->assertTrue($courierUser->hasRole('courier'));
    }

    public function test_super_admin_can_delete_courier(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->post(route('couriers.store'), [
            'first_name' => 'Silinecek',
            'last_name' => 'Kurye',
            'phone' => '0532 000 11 22',
            'email' => 'silinecek@kurye.test',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-06-01',
            'status' => 'active',
        ])->assertRedirect();

        $courier = Courier::query()->where('full_name', 'Silinecek Kurye')->firstOrFail();
        $courierUserId = $courier->user_id;

        $this->actingAs($user)
            ->delete(route('couriers.destroy', $courier->id))
            ->assertRedirect(route('couriers.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('couriers', ['id' => $courier->id]);
        $this->assertSoftDeleted('users', ['id' => $courierUserId]);
    }

    public function test_non_super_admin_cannot_delete_courier(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->post(route('couriers.store'), [
            'first_name' => 'Korunan',
            'last_name' => 'Kurye',
            'phone' => '0532 333 44 55',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-06-01',
            'status' => 'active',
        ])->assertRedirect();

        $courier = Courier::query()->where('full_name', 'Korunan Kurye')->firstOrFail();

        $ops = User::factory()->create();
        $ops->assignRole('operations_specialist');

        $this->actingAs($ops)
            ->delete(route('couriers.destroy', $courier->id))
            ->assertForbidden();

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'deleted_at' => null]);
    }
}
