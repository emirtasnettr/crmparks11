<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourierUpdateTest extends TestCase
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

    public function test_courier_can_be_updated_with_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, [
            'first_name' => 'Ahmet',
            'last_name' => 'Yıldız',
            'phone' => '0532 100 10 01',
        ]);

        $photo = UploadedFile::fake()->image('profile.jpg', 200, 200);

        $response = $this->actingAs($user)->put(route('couriers.update', $courier->id), [
            'first_name' => 'Ahmet',
            'last_name' => 'Yıldız',
            'phone' => '0532 100 10 01',
            'email' => 'ahmet.yildiz@test.local',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-01-15',
            'status' => 'active',
            'profile_photo' => $photo,
        ]);

        $response->assertRedirect(route('couriers.show', $courier->id));
        $response->assertSessionHas('success', 'Kurye bilgileri güncellendi.');

        $courier->refresh();

        $this->assertNotEmpty($courier->photo_path);

        Storage::disk('public')->assertExists($courier->photo_path);

        $showResponse = $this->actingAs($user)->get(route('couriers.show', $courier->id));

        $showResponse->assertOk();
        $showResponse->assertSee(app(\App\Modules\Courier\Services\CourierMediaService::class)->url($courier->photo_path), false);
    }

    public function test_courier_status_change_reflects_on_show_and_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, [
            'first_name' => 'Kaan',
            'last_name' => 'Aydın',
            'phone' => '0546 200 20 14',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('couriers.update', $courier->id), [
            'first_name' => 'Kaan',
            'last_name' => 'Aydın',
            'phone' => '0546 200 20 14',
            'email' => 'kaan.aydin@test.local',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-01-15',
            'status' => 'on_leave',
        ]);

        $response->assertRedirect(route('couriers.show', $courier->id));

        $courier->refresh();
        $this->assertSame('on_leave', $courier->status);

        $showResponse = $this->actingAs($user)->get(route('couriers.show', $courier->id));
        $showResponse->assertOk();
        $showResponse->assertSee('İzinli');

        $indexResponse = $this->actingAs($user)->get(route('couriers.index', ['status' => 'on_leave']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Kaan Aydın');
        $indexResponse->assertSee('İzinli');
    }

    public function test_courier_update_syncs_linked_user_without_wiping_phone(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = $this->createCourier($user, [
            'first_name' => 'Selin',
            'last_name' => 'Kara',
            'phone' => '0533 111 22 33',
            'email' => 'selin.kara@test.local',
        ]);

        $courierUser = app(\App\Modules\Courier\Services\CourierUserProvisioner::class)
            ->ensureForCourier($courier);

        $this->assertSame('0533 111 22 33', $courierUser->phone);

        $this->actingAs($user)->put(route('couriers.update', $courier->id), [
            'first_name' => 'Selin',
            'last_name' => 'Kara Güncel',
            'phone' => '0533 111 22 33',
            'email' => 'selin.kara@test.local',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-01-15',
            'status' => 'active',
        ])->assertRedirect(route('couriers.show', $courier->id));

        $courier->refresh();
        $courierUser->refresh();

        $this->assertSame('Selin Kara Güncel', $courier->full_name);
        $this->assertSame('Selin Kara Güncel', $courierUser->name);
        $this->assertSame('0533 111 22 33', $courierUser->phone);
        $this->assertSame('selin.kara@test.local', $courierUser->email);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourier(User $user, array $overrides = []): Courier
    {
        $vehicleTypeId = VehicleType::query()->where('code', 'motor')->value('id');

        return Courier::factory()->create(array_merge([
            'vehicle_type_id' => $vehicleTypeId,
            'created_by' => $user->id,
        ], $overrides));
    }
}
