<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContactIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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

        $response = $this->actingAs($user)->get(route('businesses.contacts.index'));

        $response->assertOk();
        $response->assertSee('Yetkililer');
        $response->assertSee('Mehmet Yılmaz');
        $response->assertSee('Yeni Yetkili');
        $response->assertSee('Varsayılan');
    }
}
