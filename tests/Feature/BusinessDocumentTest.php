<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessDocumentTest extends TestCase
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

    public function test_documents_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.documents.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_documents_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        Document::factory()->create([
            'documentable_type' => Business::class,
            'documentable_id' => $business->id,
            'document_category_id' => DocumentCategory::query()->where('code', 'contract')->value('id'),
            'original_name' => 'Hizmet Sözleşmesi 2026.pdf',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.documents.index'));

        $response->assertOk();
        $response->assertSee('Evraklar');
        $response->assertSee('Hizmet Sözleşmesi 2026');
        $response->assertSee('Evrak Yükle');
    }

    public function test_documents_can_be_filtered_by_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $otherBusiness = $this->createBusiness($user, [
            'company_name' => 'Diğer İşletme Ltd.',
        ]);

        Document::factory()->create([
            'documentable_type' => Business::class,
            'documentable_id' => $business->id,
            'document_category_id' => DocumentCategory::query()->where('code', 'contract')->value('id'),
            'original_name' => 'Hizmet Sözleşmesi 2026.pdf',
            'uploaded_by' => $user->id,
        ]);

        Document::factory()->create([
            'documentable_type' => Business::class,
            'documentable_id' => $otherBusiness->id,
            'document_category_id' => DocumentCategory::query()->where('code', 'contract')->value('id'),
            'original_name' => 'Çerçeve Sözleşme.pdf',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.documents.index', [
            'business_id' => $business->id,
        ]));

        $response->assertOk();
        $response->assertSee('Hizmet Sözleşmesi 2026');
        $response->assertDontSee('Çerçeve Sözleşme');
    }

    public function test_business_document_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $response = $this->actingAs($user)->post(route('businesses.documents.store'), [
            'business_id' => $business->id,
            'document_type' => 'tax_plate',
            'file' => UploadedFile::fake()->create('vergi-levhasi.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('businesses.documents.index', ['business_id' => $business->id]));

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Business::class,
            'documentable_id' => $business->id,
            'original_name' => 'vergi-levhasi.pdf',
        ]);
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
