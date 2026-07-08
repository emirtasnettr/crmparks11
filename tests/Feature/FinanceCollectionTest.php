<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceCollectionDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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
        $response->assertSee('TAH-2026-000039');
        $response->assertSee('Kısmi Tahsil Edildi');
        $response->assertSee('Vadesi Geçti');
    }

    public function test_user_without_financial_permission_cannot_view_collections(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.collections.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_collection_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.collections.show', 16));

        $response->assertOk();
        $response->assertSee('Tahsilat Detayı');
        $response->assertSee('TAH-2026-000016');
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Gelir Bilgisi');
        $response->assertSee('Fatura Bilgisi');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Tahsilat Geçmişi');
        $response->assertSee('Dekontlar');
        $response->assertSee('Notlar');
    }

    public function test_dummy_data_has_sixty_five_collection_records_with_mixed_statuses(): void
    {
        $collections = FinanceCollectionDummyData::all();

        $this->assertGreaterThanOrEqual(60, count($collections));
        $this->assertCount(65, $collections);
        $this->assertGreaterThan(0, collect($collections)->where('status', 'collected')->count());
        $this->assertGreaterThan(0, collect($collections)->where('status', 'partial')->count());
        $this->assertGreaterThan(0, collect($collections)->where('status', 'pending')->count());
        $this->assertGreaterThan(0, collect($collections)->where('status', 'overdue')->count());
    }

    public function test_remaining_amount_is_calculated_correctly(): void
    {
        $collection = FinanceCollectionDummyData::find(16);

        $this->assertNotNull($collection);
        $this->assertEquals(
            round($collection['total_amount'] - $collection['collected_amount'], 2),
            $collection['remaining_amount']
        );
    }

    public function test_collections_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.collections.index', [
            'collection_status' => 'overdue',
        ]));

        $response->assertOk();
        $response->assertSee('Vadesi Geçti');
        $response->assertSee('tahsilat kaydı listeleniyor');
    }

    public function test_partial_collection_has_payment_history(): void
    {
        $collection = FinanceCollectionDummyData::find(21);

        $this->assertEquals('partial', $collection['status']);
        $this->assertGreaterThanOrEqual(2, count($collection['collection_history']));
    }

    public function test_collection_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.collections.show', 9999));

        $response->assertNotFound();
    }
}
