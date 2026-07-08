<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_agency_create_requires_authentication(): void
    {
        $response = $this->get(route('agencies.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.create'));

        $response->assertOk();
        $response->assertSee('Yeni Acente');
        $response->assertSee('Sisteme yeni bir acente kaydı oluşturun.');
        $response->assertSee('Genel Bilgiler');
        $response->assertSee('Vergi Bilgileri');
        $response->assertSee('Adres Bilgileri');
        $response->assertSee('Finans Bilgileri');
        $response->assertSee('Banka Bilgileri');
        $response->assertSee('MERSİS No');
        $response->assertSee('Varsayılan Komisyon Oranı');
        $response->assertSee('15 Günlük');
        $response->assertSee('Beklemede');
        $response->assertSee('agency-form');
    }
}
