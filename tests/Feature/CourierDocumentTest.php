<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourierDocumentTest extends TestCase
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

    public function test_documents_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.documents.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_documents_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, [
            'full_name' => 'Ahmet Yıldız',
        ]);

        Document::factory()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => DocumentCategory::query()->where('code', 'identity')->value('id'),
            'original_name' => 'KIM-12345678901.pdf',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('couriers.documents.index'));

        $response->assertOk();
        $response->assertSee('Belgeler');
        $response->assertSee('Kuryelere ait tüm belgeleri buradan yönetin.');
        $response->assertSee('Belge Yükle');
        $response->assertSee('Toplam Belge');
        $response->assertSee('Süresi Yaklaşan Belgeler');
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('KIM-12345678901');
    }

    public function test_document_status_is_computed_from_expiry_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);
        $categoryId = DocumentCategory::query()->where('code', 'identity')->value('id');

        $expiring = Document::factory()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => $categoryId,
            'uploaded_by' => $user->id,
            'expires_at' => now()->addDays(10),
        ]);

        $expired = Document::factory()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => $categoryId,
            'uploaded_by' => $user->id,
            'expires_at' => now()->subDay(),
        ]);

        $valid = Document::factory()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => $categoryId,
            'uploaded_by' => $user->id,
            'expires_at' => now()->addMonths(6),
        ]);

        $presenter = app(\App\Modules\Courier\Services\CourierDocumentPresenter::class);

        $expiringRow = $presenter->indexRow($expiring);
        $expiredRow = $presenter->indexRow($expired);
        $validRow = $presenter->indexRow($valid);

        $this->assertSame('expiring_soon', $expiringRow['status']);
        $this->assertSame('expired', $expiredRow['status']);
        $this->assertSame('valid', $validRow['status']);
        $this->assertLessThanOrEqual(30, $expiringRow['days_remaining']);
        $this->assertLessThan(0, $expiredRow['days_remaining']);
        $this->assertGreaterThan(30, $validRow['days_remaining']);
    }

    public function test_authenticated_user_can_view_document_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, [
            'full_name' => 'Ahmet Yıldız',
        ]);

        $document = Document::factory()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => DocumentCategory::query()->where('code', 'identity')->value('id'),
            'original_name' => 'KIM-12345678901.pdf',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('couriers.documents.show', $document->id));

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
        $courier = $this->createCourier($user);
        $categoryId = DocumentCategory::query()->where('code', 'identity')->value('id');

        Document::factory()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => $categoryId,
            'original_name' => 'KIM-12345678901.pdf',
            'uploaded_by' => $user->id,
            'expires_at' => now()->subDay(),
        ]);

        Document::factory()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => $categoryId,
            'original_name' => 'GECERLI-BELGE.pdf',
            'uploaded_by' => $user->id,
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($user)->get(route('couriers.documents.index', [
            'status' => 'expired',
        ]));

        $response->assertOk();
        $response->assertSee('Süresi Dolmuş');
        $response->assertSee('KIM-12345678901');
        $response->assertDontSee('GECERLI-BELGE');
    }

    public function test_courier_document_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('couriers.documents.store'), [
            'courier_id' => $courier->id,
            'document_type' => 'identity',
            'document_number' => 'KIM-99887766554',
            'file' => UploadedFile::fake()->create('kimlik.pdf', 100, 'application/pdf'),
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $response->assertRedirect(route('couriers.documents.index', ['courier_id' => $courier->id]));

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'original_name' => 'KIM-99887766554.pdf',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourier(User $user, array $overrides = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
