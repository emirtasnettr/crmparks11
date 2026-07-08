<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Services\AgencyProfileStore;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgencyUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_agency_can_be_updated_with_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user)->put(route('agencies.update', 1), [
            'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
            'phone' => '0212 555 00 01',
            'tax_number' => '1234567890',
            'city' => 'İstanbul',
            'district' => 'Şişli',
            'address' => 'Test adres',
            'status' => 'active',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('agencies.show', 1));
        $response->assertSessionHas('success', 'Acente bilgileri güncellendi.');

        $stored = AgencyProfileStore::get(1);

        $this->assertNotEmpty($stored['logo_path']);
        $this->assertNotEmpty($stored['logo_url']);

        Storage::disk('public')->assertExists($stored['logo_path']);

        $showResponse = $this->actingAs($user)->get(route('agencies.show', 1));

        $showResponse->assertOk();
        $showResponse->assertSee($stored['logo_url'], false);
    }

    public function test_agency_status_change_reflects_on_show_and_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->put(route('agencies.update', 9), [
            'company_name' => 'Konya Merkez Lojistik',
            'phone' => '0332 555 90 09',
            'tax_number' => '9012345678',
            'city' => 'Konya',
            'district' => 'Selçuklu',
            'address' => 'Test adres',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('agencies.show', 9));

        $stored = AgencyProfileStore::get(9);
        $this->assertSame('pending', $stored['status']);

        $showResponse = $this->actingAs($user)->get(route('agencies.show', 9));
        $showResponse->assertOk();
        $showResponse->assertSee('Beklemede');

        $indexResponse = $this->actingAs($user)->get(route('agencies.index', ['status' => 'pending']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Konya Merkez Lojistik');
        $indexResponse->assertSee('Beklemede');
    }
}
