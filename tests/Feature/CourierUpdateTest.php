<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Services\CourierProfileStore;
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

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_courier_can_be_updated_with_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $photo = UploadedFile::fake()->image('profile.jpg', 200, 200);

        $response = $this->actingAs($user)->put(route('couriers.update', 1), [
            'first_name' => 'Ahmet',
            'last_name' => 'Yıldız',
            'phone' => '0532 100 10 01',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-01-15',
            'status' => 'active',
            'profile_photo' => $photo,
        ]);

        $response->assertRedirect(route('couriers.show', 1));
        $response->assertSessionHas('success', 'Kurye bilgileri güncellendi.');

        $stored = CourierProfileStore::get(1);

        $this->assertNotEmpty($stored['photo_path']);
        $this->assertNotEmpty($stored['photo_url']);

        Storage::disk('public')->assertExists($stored['photo_path']);

        $showResponse = $this->actingAs($user)->get(route('couriers.show', 1));

        $showResponse->assertOk();
        $showResponse->assertSee($stored['photo_url'], false);
    }

    public function test_courier_status_change_reflects_on_show_and_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->put(route('couriers.update', 14), [
            'first_name' => 'Kaan',
            'last_name' => 'Aydın',
            'phone' => '0546 200 20 14',
            'courier_type' => 'independent',
            'vehicle_type' => 'motorcycle',
            'start_date' => '2024-01-15',
            'status' => 'on_leave',
        ]);

        $response->assertRedirect(route('couriers.show', 14));

        $stored = CourierProfileStore::get(14);
        $this->assertSame('on_leave', $stored['status']);

        $showResponse = $this->actingAs($user)->get(route('couriers.show', 14));
        $showResponse->assertOk();
        $showResponse->assertSee('İzinli');

        $indexResponse = $this->actingAs($user)->get(route('couriers.index', ['status' => 'on_leave']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Kaan Aydın');
        $indexResponse->assertSee('İzinli');
    }
}
