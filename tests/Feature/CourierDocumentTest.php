<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Data\CourierDocumentDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_documents_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.documents.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_documents_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.documents.index'));

        $response->assertOk();
        $response->assertSee('Belgeler');
        $response->assertSee('Kuryelere ait tüm belgeleri buradan yönetin.');
        $response->assertSee('Belge Yükle');
        $response->assertSee('Toplam Belge');
        $response->assertSee('Süresi Yaklaşan Belgeler');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_document_status_is_computed_from_expiry_date(): void
    {
        $documents = CourierDocumentDummyData::all();

        $expiring = collect($documents)->firstWhere('status', 'expiring_soon');
        $expired = collect($documents)->firstWhere('status', 'expired');
        $valid = collect($documents)->firstWhere('status', 'valid');

        $this->assertNotNull($expiring);
        $this->assertNotNull($expired);
        $this->assertNotNull($valid);
        $this->assertLessThanOrEqual(30, $expiring['days_remaining']);
        $this->assertLessThan(0, $expired['days_remaining']);
        $this->assertGreaterThan(30, $valid['days_remaining']);
    }

    public function test_authenticated_user_can_view_document_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.documents.show', 1));

        $response->assertOk();
        $response->assertSee('Kurye Bilgisi');
        $response->assertSee('Belge Bilgisi');
        $response->assertSee('Dosya Önizleme');
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('KIM-12345678901');
    }

    public function test_documents_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.documents.index', [
            'status' => 'expired',
        ]));

        $response->assertOk();
        $response->assertSee('Süresi Dolmuş');
        $response->assertDontSee('KIM-12345678901');
    }
}
