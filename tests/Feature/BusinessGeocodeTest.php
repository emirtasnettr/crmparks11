<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Services\BusinessGeocodeService;
use Database\Seeders\CitySeeder;
use Database\Seeders\NeighborhoodSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessGeocodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(NeighborhoodSeeder::class);
    }

    public function test_geocode_endpoint_returns_coordinates(): void
    {
        Http::fake([
            'photon.komoot.io/*' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'geometry' => [
                            'coordinates' => [28.9784, 41.0082],
                        ],
                        'properties' => [
                            'name' => 'Caferağa Mahallesi',
                            'city' => 'İstanbul',
                            'country' => 'Türkiye',
                        ],
                    ],
                ],
            ], 200),
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->postJson(route('businesses.geocode'), [
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'neighborhood' => 'Caferağa',
                'address' => '',
            ])
            ->assertOk()
            ->assertJsonPath('latitude', 41.0082)
            ->assertJsonPath('longitude', 28.9784);
    }

    public function test_neighborhoods_endpoint_returns_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)
            ->getJson(route('businesses.neighborhoods', [
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
            ]))
            ->assertOk()
            ->json('neighborhoods');

        $this->assertIsArray($response);
        $this->assertNotEmpty($response);
        $this->assertContains('Caferağa', $response);
    }

    public function test_geocode_falls_back_to_nominatim_when_photon_empty(): void
    {
        Http::fake([
            'photon.komoot.io/*' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [],
            ], 200),
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '41.0422000',
                    'lon' => '29.0067000',
                    'display_name' => 'Beşiktaş, İstanbul, Türkiye',
                ],
            ], 200),
        ]);

        $result = app(BusinessGeocodeService::class)->locate(
            'İstanbul',
            'Beşiktaş',
            'Levent',
            ''
        );

        $this->assertNotNull($result);
        $this->assertSame(41.0422, $result['latitude']);
        $this->assertSame(29.0067, $result['longitude']);
    }

    public function test_geocode_service_returns_null_when_empty(): void
    {
        $result = app(BusinessGeocodeService::class)->locate('', '', '', '');
        $this->assertNull($result);
    }
}
