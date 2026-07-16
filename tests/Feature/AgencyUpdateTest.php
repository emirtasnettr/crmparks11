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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgencyUpdateTest extends TestCase
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

    public function test_agency_can_be_updated_with_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user, [
            'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
            'tax_number' => '1234567890',
        ]);

        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user)->put(route('agencies.update', $agency->id), [
            'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
            'brand_name' => 'Hızlı Kurye',
            'phone' => '0212 555 00 01',
            'tax_number' => '1234567890',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'status' => 'active',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('agencies.show', $agency->id));
        $response->assertSessionHas('success', 'Acente bilgileri güncellendi.');

        $agency->refresh();

        $this->assertNotEmpty($agency->logo_path);

        Storage::disk('public')->assertExists($agency->logo_path);

        $showResponse = $this->actingAs($user)->get(route('agencies.show', $agency->id));

        $showResponse->assertOk();
        $showResponse->assertSee(app(\App\Modules\Agency\Services\AgencyMediaService::class)->url($agency->logo_path), false);
    }

    public function test_agency_status_change_reflects_on_show_and_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user, [
            'company_name' => 'Konya Merkez Lojistik',
            'tax_number' => '9012345678',
            'status' => 'inactive',
            'city' => 'Konya',
            'district' => 'Selçuklu',
        ]);

        $response = $this->actingAs($user)->put(route('agencies.update', $agency->id), [
            'company_name' => 'Konya Merkez Lojistik',
            'brand_name' => 'Konya Merkez',
            'phone' => '0332 555 90 09',
            'tax_number' => '9012345678',
            'city' => 'Konya',
            'district' => 'Selçuklu',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('agencies.show', $agency->id));

        $agency->refresh();
        $this->assertSame('pending', $agency->status);

        $showResponse = $this->actingAs($user)->get(route('agencies.show', $agency->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Beklemede');

        $indexResponse = $this->actingAs($user)->get(route('agencies.index', ['status' => 'pending']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Konya Merkez Lojistik');
        $indexResponse->assertSee('Beklemede');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAgency(User $user, array $overrides = []): Agency
    {
        $cityName = $overrides['city'] ?? 'İstanbul';
        $districtName = $overrides['district'] ?? 'Kadıköy';
        unset($overrides['city'], $overrides['district']);

        $city = City::query()->where('name', $cityName)->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', $districtName)
            ->firstOrFail();

        return Agency::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }
}
