<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\District;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgencyDocumentTest extends TestCase
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

    public function test_agency_documents_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.documents.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_documents_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);

        Document::factory()->create([
            'documentable_type' => Agency::class,
            'documentable_id' => $agency->id,
            'document_category_id' => DocumentCategory::query()->where('code', 'tax_plate')->value('id'),
            'original_name' => 'VL-1234567890.pdf',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('agencies.documents.index'));

        $response->assertOk();
        $response->assertSee('Evraklar');
        $response->assertSee('Evrak Yükle');
        $response->assertSee('VL-1234567890');
        $response->assertSee($agency->company_name);
    }

    public function test_agency_document_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);

        $response = $this->actingAs($user)->post(route('agencies.documents.store'), [
            'agency_id' => $agency->id,
            'document_type' => 'tax_plate',
            'file' => UploadedFile::fake()->create('vergi-levhasi.pdf', 100, 'application/pdf'),
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $response->assertRedirect(route('agencies.documents.index', ['agency_id' => $agency->id]));

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Agency::class,
            'documentable_id' => $agency->id,
            'original_name' => 'vergi-levhasi.pdf',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAgency(User $user, array $overrides = []): Agency
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Agency::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }
}
