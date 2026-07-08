<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContactIndexTest extends TestCase
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

    public function test_business_contacts_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.contacts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_business_contacts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        BusinessContact::factory()->create([
            'business_id' => $business->id,
            'full_name' => 'Mehmet Yılmaz',
            'title' => 'İşletme Sahibi',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.contacts.index'));

        $response->assertOk();
        $response->assertSee('Yetkililer');
        $response->assertSee('Mehmet Yılmaz');
        $response->assertSee('Yeni Yetkili');
        $response->assertSee('Varsayılan');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBusiness(User $user, array $overrides = []): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Business::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }
}
