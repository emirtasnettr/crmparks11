<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Models\Courier;
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
    }
}
