<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_assignments_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.assignments.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_assignments_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.assignments.index'));

        $response->assertOk();
        $response->assertSee('Atanan Kuryeler');
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('Yeni Kurye Ataması');
        $response->assertSee('Aktif Atama');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
    }

    public function test_authenticated_user_can_view_assignment_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.assignments.show', 3));

        $response->assertOk();
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('Atama Tarihleri');
        $response->assertSee('Ahmet Yıldız');
    }
}
