<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Document;
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

class BusinessContractTest extends TestCase
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

        Storage::fake('public');
    }

    public function test_contracts_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.contracts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_contracts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'contract_number' => 'SZL-2026-001',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.contracts.index'));

        $response->assertOk();
        $response->assertSee('Sözleşmeler');
        $response->assertSee('SZL-2026-001');
        $response->assertSee('Yeni Sözleşme');
        $response->assertSee($business->company_name);
    }

    public function test_authenticated_user_can_view_contract_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, [
            'company_name' => 'Burger House Gıda Ltd. Şti.',
        ]);

        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.contracts.show', $contract->id));

        $response->assertOk();
        $response->assertSee('Sözleşme Bilgileri');
        $response->assertSee('Burger House');
        $response->assertSee('Düzenle');
    }

    public function test_business_contract_can_be_updated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'contract_number' => 'SZL-2026-001',
            'notes' => 'Eski not',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('businesses.contracts.update', $contract->id), [
            'business_id' => $business->id,
            'contract_number' => 'SZL-2026-001',
            'contract_type' => 'service',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'notes' => 'Güncel not',
            'redirect_to_contract' => true,
        ]);

        $response->assertRedirect(route('businesses.contracts.show', $contract->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'notes' => 'Güncel not',
        ]);
    }

    public function test_contract_file_is_shown_on_detail_page_after_upload(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $response = $this->actingAs($user)->post(route('businesses.contracts.store'), [
            'business_id' => $business->id,
            'contract_number' => 'SZL-2026-FILE',
            'contract_type' => 'service',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'contract_file' => UploadedFile::fake()->create('sozlesme.pdf', 100, 'application/pdf'),
        ]);

        $contract = Contract::query()->where('contract_number', 'SZL-2026-FILE')->firstOrFail();

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Contract::class,
            'documentable_id' => $contract->id,
            'original_name' => 'sozlesme.pdf',
        ]);

        $contract->refresh();
        $this->assertNotNull($contract->document_id);

        $this->actingAs($user)
            ->get(route('businesses.contracts.show', $contract->id))
            ->assertOk()
            ->assertSee('sozlesme.pdf')
            ->assertDontSee('Bu sözleşmeye henüz dosya yüklenmemiş.');

        $this->actingAs($user)
            ->get(route('businesses.contracts.download', $contract->id))
            ->assertOk();
    }

    public function test_super_admin_can_delete_contract(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('businesses.contracts.destroy', $contract->id))
            ->assertRedirect(route('businesses.contracts.index', ['business_id' => $business->id]));

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }

    public function test_legacy_business_contract_document_is_shown_on_detail_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'created_by' => $user->id,
        ]);

        $path = UploadedFile::fake()->create('eski-sozlesme.pdf', 100, 'application/pdf')
            ->storeAs('documents/business/'.$business->id, 'eski-sozlesme.pdf', 'public');

        $categoryId = \App\Models\DocumentCategory::query()->where('code', 'contract')->value('id');

        Document::query()->create([
            'documentable_type' => Business::class,
            'documentable_id' => $business->id,
            'document_category_id' => $categoryId,
            'original_name' => 'eski-sozlesme.pdf',
            'stored_name' => 'eski-sozlesme.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'disk' => 'public',
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('businesses.contracts.show', $contract->id))
            ->assertOk()
            ->assertSee('eski-sozlesme.pdf')
            ->assertDontSee('Bu sözleşmeye henüz dosya yüklenmemiş.');

        $contract->refresh();
        $this->assertNotNull($contract->document_id);
    }

    public function test_non_super_admin_cannot_delete_contract(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales_manager');
        $business = $this->createBusiness($user);

        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('businesses.contracts.destroy', $contract->id))
            ->assertForbidden();

        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
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
