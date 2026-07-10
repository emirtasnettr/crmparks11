<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentContractContactActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);

        Storage::fake('public');
    }

    public function test_business_document_can_be_downloaded_and_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create(['created_by' => $user->id]);
        $category = DocumentCategory::query()->firstOrFail();

        $path = UploadedFile::fake()->create('sozlesme.pdf', 100, 'application/pdf')
            ->storeAs('documents/business/'.$business->id, 'sozlesme.pdf', 'public');

        $document = Document::query()->create([
            'documentable_type' => Business::class,
            'documentable_id' => $business->id,
            'document_category_id' => $category->id,
            'original_name' => 'sozlesme.pdf',
            'stored_name' => 'sozlesme.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'disk' => 'public',
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('businesses.documents.download', $document->id))
            ->assertOk();

        $this->actingAs($user)
            ->delete(route('businesses.documents.destroy', $document->id))
            ->assertRedirect(route('businesses.documents.index', ['business_id' => $business->id]));

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_business_contact_and_contract_can_be_deactivated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create(['created_by' => $user->id]);

        $contact = BusinessContact::query()->create([
            'business_id' => $business->id,
            'full_name' => 'Ali Yetkili',
            'title' => 'manager',
            'phone' => '0532 000 00 00',
            'status' => 'active',
        ]);

        $contractType = ContractType::query()->firstOrFail();
        $contract = Contract::query()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => $contractType->id,
            'title' => 'Test Sözleşme',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('businesses.contacts.deactivate', $contact->id))
            ->assertRedirect(route('businesses.contacts.index', ['business_id' => $business->id]));

        $this->assertSame('inactive', $contact->fresh()->status);

        $this->actingAs($user)
            ->post(route('businesses.contracts.deactivate', $contract->id))
            ->assertRedirect(route('businesses.contracts.index', ['business_id' => $business->id]));

        $this->assertSame('cancelled', $contract->fresh()->status);
    }

    public function test_agency_contact_and_contract_can_be_deactivated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = Agency::factory()->create(['created_by' => $user->id]);

        $contact = AgencyContact::query()->create([
            'agency_id' => $agency->id,
            'full_name' => 'Acente Yetkili',
            'title' => 'manager',
            'phone' => '0532 111 11 11',
            'status' => 'active',
        ]);

        $contractType = ContractType::query()->firstOrFail();
        $contract = Contract::query()->create([
            'contractable_type' => Agency::class,
            'contractable_id' => $agency->id,
            'contract_type_id' => $contractType->id,
            'title' => 'Acente Sözleşme',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('agencies.contacts.deactivate', $contact->id))
            ->assertRedirect();

        $this->assertSame('inactive', $contact->fresh()->status);

        $this->actingAs($user)
            ->post(route('agencies.contracts.deactivate', $contract->id))
            ->assertRedirect();

        $this->assertSame('cancelled', $contract->fresh()->status);
    }

    public function test_courier_document_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create(['created_by' => $user->id]);
        $category = DocumentCategory::query()->firstOrFail();

        $path = UploadedFile::fake()->create('ehliyet.pdf', 50, 'application/pdf')
            ->storeAs('documents/courier/'.$courier->id, 'ehliyet.pdf', 'public');

        $document = Document::query()->create([
            'documentable_type' => Courier::class,
            'documentable_id' => $courier->id,
            'document_category_id' => $category->id,
            'original_name' => 'ehliyet.pdf',
            'stored_name' => 'ehliyet.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 512,
            'disk' => 'public',
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('couriers.documents.destroy', $document->id))
            ->assertRedirect(route('couriers.documents.index', ['courier_id' => $courier->id]));

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }
}
