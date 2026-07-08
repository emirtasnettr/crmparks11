<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_courier_create_requires_authentication(): void
    {
        $response = $this->get(route('couriers.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_courier_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.create'));

        $response->assertOk();
        $response->assertSee('Yeni Kurye');
        $response->assertSee('Sisteme yeni bir kurye kaydı oluşturun.');
        $response->assertSee('Genel Bilgiler');
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('Vergi Bilgileri');
        $response->assertSee('Araç Bilgileri');
        $response->assertSee('Banka Bilgileri');
        $response->assertSee('Esnaf Kurye');
        $response->assertSee('Acente Kuryesi');
        $response->assertSee('courier-form');
    }
}
