<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyCreateTest extends TestCase
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
        $response->assertSee('Adres Bilgileri');
        $response->assertSee('Vergi Numarası');
        $response->assertDontSee('name="mersis_number"', false);
        $response->assertDontSee('name="commission_rate"', false);
        $response->assertDontSee('name="iban"', false);
        $response->assertDontSee('name="tax_office"', false);
        $response->assertDontSee('name="address"', false);
        $response->assertSee('Beklemede');
        $response->assertSee('agency-form');
        $response->assertSee(route('agencies.store'), false);
    }
}
