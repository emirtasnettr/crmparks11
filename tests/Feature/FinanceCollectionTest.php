<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\Finance\Services\CollectionPresenter;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCollectionTest extends TestCase
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

    public function test_collections_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.collections.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_collections_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['company_name' => 'Burger House Gıda Ltd. Şti.']);
        $partial = FinanceCollection::factory()->partial()->for($business)->create();
        FinanceCollection::factory()->overdue()->for($business)->create();

        $response = $this->actingAs($user)->get(route('finance.collections.index'));

        $response->assertOk();
        $response->assertSee('Tahsilatlar');
        $response->assertSee('İşletmelerden alınacak tüm tahsilatları yönetin.');
        $response->assertSee('Yeni Tahsilat');
        $response->assertSee('Toplu Tahsilat');
        $response->assertSee('Toplam Tahsilat');
        $response->assertSee('Tahsil Edilen');
        $response->assertSee('Bekleyen Tahsilat');
        $response->assertSee('Geciken Tahsilat');
        $response->assertSee('Bugün Tahsil Edilen');
        $response->assertSee('Bu Ay Tahsil Edilen');
        $response->assertSee($partial->reference);
        $response->assertSee('Kısmi Tahsil Edildi');
        $response->assertSee('Vadesi Geçti');
    }

    public function test_user_without_financial_permission_cannot_view_collections(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->get(route('finance.collections.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_collection_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $collection = FinanceCollection::factory()
            ->forRevenue()
            ->collected()
            ->create([
                'notes' => 'Muhasebe tarafından kontrol edildi.',
            ]);

        $response = $this->actingAs($user)->get(route('finance.collections.show', $collection->id));

        $response->assertOk();
        $response->assertSee('Tahsilat Detayı');
        $response->assertSee($collection->reference);
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Gelir Bilgisi');
        $response->assertSee('Fatura Bilgisi');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Tahsilat Geçmişi');
        $response->assertSee('Dekontlar');
        $response->assertSee('Notlar');
        $response->assertSee('Muhasebe tarafından kontrol edildi.');
    }

    public function test_remaining_amount_is_calculated_correctly(): void
    {
        $collection = FinanceCollection::factory()->partial()->create();
        $presented = app(CollectionPresenter::class)->showRow($collection->fresh(['business.city', 'revenue', 'currentAccount', 'payments']));

        $this->assertEquals(
            round((float) $collection->total_amount - (float) $collection->collected_amount, 2),
            $presented['remaining_amount']
        );
    }

    public function test_collections_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceCollection::factory()->overdue()->create();
        FinanceCollection::factory()->collected()->create();

        $response = $this->actingAs($user)->get(route('finance.collections.index', [
            'collection_status' => 'overdue',
        ]));

        $response->assertOk();
        $response->assertSee('Vadesi Geçti');
        $response->assertSee('tahsilat kaydı listeleniyor');
    }

    public function test_partial_collection_has_payment_history(): void
    {
        $collection = FinanceCollection::factory()->partial()->create()->fresh(['payments']);
        $presented = app(CollectionPresenter::class)->showRow($collection);

        $this->assertEquals('partial', $collection->status);
        $this->assertGreaterThanOrEqual(2, count($presented['collection_history']));
    }

    public function test_collection_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.collections.show', 9999));

        $response->assertNotFound();
    }

    public function test_user_can_create_collection_with_payment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'company_name' => 'Test İşletme A.Ş.',
            'brand_name' => 'Test Marka',
        ]);

        $response = $this->actingAs($user)->post(route('finance.collections.store'), [
            'business_id' => $business->id,
            'invoice_no' => 'FTR-2026-0100',
            'due_date' => '2026-07-20',
            'collection_date' => '2026-07-09',
            'total_amount' => 25000,
            'collected_amount' => 25000,
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'REF-2026-00099',
            'bank' => 'Garanti BBVA',
            'description' => 'Tam tahsilat testi',
        ]);

        $response->assertRedirect(route('finance.collections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_collections', [
            'business_id' => $business->id,
            'total_amount' => 25000,
            'collected_amount' => 25000,
            'status' => 'collected',
            'invoice_no' => 'FTR-2026-0100',
        ]);

        $this->assertDatabaseHas('finance_collection_payments', [
            'amount' => 25000,
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertDatabaseHas('current_accounts', [
            'accountable_type' => Business::class,
            'accountable_id' => $business->id,
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'type' => 'collection',
            'credit' => 25000,
        ]);
    }

    public function test_user_can_create_pending_collection_linked_to_revenue(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $revenue = FinanceRevenue::factory()->withInvoice('FTR-2026-0200')->create();

        $response = $this->actingAs($user)->post(route('finance.collections.store'), [
            'business_id' => $revenue->business_id,
            'revenue_id' => $revenue->id,
            'due_date' => '2026-07-25',
            'total_amount' => 18000,
            'collected_amount' => 0,
        ]);

        $response->assertRedirect(route('finance.collections.index'));

        $this->assertDatabaseHas('finance_collections', [
            'business_id' => $revenue->business_id,
            'revenue_id' => $revenue->id,
            'source' => 'revenue',
            'total_amount' => 18000,
            'collected_amount' => 0,
            'status' => 'pending',
            'invoice_no' => 'FTR-2026-0200',
        ]);
    }
}
