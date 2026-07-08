<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_activities_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.activities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_activities_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.activities.index'));

        $response->assertOk();
        $response->assertSee('Hareket Geçmişi');
        $response->assertSee('İşletmeler üzerinde yapılan tüm işlemleri görüntüleyin.');
        $response->assertSee('Kurye Atandı');
        $response->assertSee('Evrak Yüklendi');
        $response->assertSee('Hakediş Oluşturuldu');
    }

    public function test_activities_can_be_filtered_by_action(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.activities.index', [
            'action' => 'business_created',
        ]));

        $response->assertOk();
        $response->assertSee('Burger House işletmesi sisteme kaydedildi.');
        $response->assertDontSee('Ali Demir kuryesi operasyona atandı.');
    }
}
