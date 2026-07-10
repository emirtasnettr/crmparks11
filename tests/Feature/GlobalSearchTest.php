<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }

    public function test_search_requires_authentication(): void
    {
        $this->get(route('search', ['q' => 'test']))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_search_entities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Business::factory()->create(['company_name' => 'Burger House Ltd']);
        Courier::factory()->create(['full_name' => 'Ahmet Kurye']);
        Agency::factory()->create(['company_name' => 'Anadolu Acente']);

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'Burger']));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.groups.0.key', 'businesses')
            ->assertJsonPath('data.groups.0.items.0.title', 'Burger House Ltd');
    }

    public function test_short_query_returns_empty_groups(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->getJson(route('search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.groups', []);
    }
}
