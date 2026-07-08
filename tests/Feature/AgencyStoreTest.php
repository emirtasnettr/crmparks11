<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyStoreTest extends TestCase
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

    public function test_agency_store_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('agencies.store'), [
            'company_name' => 'Yeni Acente Ltd. Şti.',
            'phone' => '0212 111 22 33',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'address' => 'Test adres',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('agencies', 0);
    }

    public function test_agency_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->post(route('agencies.store'), [
            'company_name' => 'Point Kurye Acente Ltd. Şti.',
            'brand_name' => 'Point Acente',
            'phone' => '0216 444 55 66',
            'email' => 'info@pointacente.test',
            'website' => 'https://pointacente.test',
            'tax_office' => 'Kadıköy',
            'tax_number' => '1234567890',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'address' => 'Test Mahallesi No:1',
            'commission_rate' => '15',
            'payment_period' => 'monthly',
            'status' => 'active',
            'notes' => 'Canlı kayıt testi',
        ]);

        $agency = Agency::query()->first();

        $this->assertNotNull($agency);
        $response->assertRedirect(route('agencies.show', $agency->id));
        $response->assertSessionHas('success', 'Acente başarıyla oluşturuldu.');

        $this->assertSame('Point Kurye Acente Ltd. Şti.', $agency->company_name);
        $this->assertSame('Point Acente', $agency->brand_name);
        $this->assertSame('1234567890', $agency->tax_number);
        $this->assertSame('Canlı kayıt testi', $agency->notes);

        $indexResponse = $this->actingAs($user)->get(route('agencies.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Point Kurye Acente Ltd. Şti.');
    }
}
