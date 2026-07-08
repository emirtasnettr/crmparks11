<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_documents_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.documents.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_documents_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.documents.index'));

        $response->assertOk();
        $response->assertSee('Evraklar');
        $response->assertSee('İşletmelere ait tüm evrakları yönetin.');
        $response->assertSee('Evrak Yükle');
        $response->assertSee('Hizmet Sözleşmesi 2026');
        $response->assertSee('Vergi Levhası');
    }

    public function test_documents_can_be_filtered_by_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.documents.index', [
            'business_id' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Hizmet Sözleşmesi 2026');
        $response->assertDontSee('Çerçeve Sözleşme');
    }
}
