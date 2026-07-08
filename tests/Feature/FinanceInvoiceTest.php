<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceInvoiceDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_invoices_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.invoices.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_invoices_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.invoices.index'));

        $response->assertOk();
        $response->assertSee('Faturalar');
        $response->assertSee('İşletmelere ait tüm faturaları yönetin.');
        $response->assertSee('Yeni Fatura');
        $response->assertSee('Toplu Fatura Oluştur');
        $response->assertSee('PDF');
        $response->assertSee('Aktar');
        $response->assertSee('Toplam Fatura');
        $response->assertSee('Bu Ay Kesilen');
        $response->assertSee('Tahsil Edilen');
        $response->assertSee('İptal Edilen');
        $response->assertSee('Kısmi Tahsil');
        $response->assertSee('fatura kaydı listeleniyor');
    }

    public function test_user_without_financial_permission_cannot_view_invoices(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.invoices.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_invoice_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.invoices.show', 10));

        $response->assertOk();
        $response->assertSee('Fatura Detayı');
        $response->assertSee('FTR-2026-000010');
        $response->assertSee('Fatura Bilgileri');
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Hakediş Bilgileri');
        $response->assertSee('Tahsilat Bilgileri');
        $response->assertSee('Cari Hareketleri');
        $response->assertSee('PDF Önizleme');
    }

    public function test_dummy_data_has_eighty_five_invoice_records_with_mixed_types(): void
    {
        $invoices = FinanceInvoiceDummyData::all();

        $this->assertGreaterThanOrEqual(80, count($invoices));
        $this->assertCount(85, $invoices);
        $this->assertGreaterThan(0, collect($invoices)->where('invoice_type', 'e_invoice')->count());
        $this->assertGreaterThan(0, collect($invoices)->where('invoice_type', 'e_archive')->count());
        $this->assertGreaterThan(0, collect($invoices)->where('invoice_type', 'manual')->count());
        $this->assertGreaterThan(0, collect($invoices)->where('collection_status', 'collected')->count());
        $this->assertGreaterThan(0, collect($invoices)->where('collection_status', 'partial')->count());
        $this->assertGreaterThan(0, collect($invoices)->where('collection_status', 'pending')->count());
    }

    public function test_grand_total_is_calculated_correctly(): void
    {
        $invoice = FinanceInvoiceDummyData::find(10);

        $this->assertNotNull($invoice);
        $this->assertEquals(
            round($invoice['subtotal'] + $invoice['vat_amount'], 2),
            $invoice['grand_total']
        );
    }

    public function test_each_earning_has_at_most_one_invoice(): void
    {
        $earningIds = collect(FinanceInvoiceDummyData::all())
            ->pluck('earning_id')
            ->filter()
            ->values();

        $this->assertEquals($earningIds->count(), $earningIds->unique()->count());
    }

    public function test_invoices_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.invoices.index', [
            'invoice_type' => 'e_invoice',
        ]));

        $response->assertOk();
        $response->assertSee('fatura kaydı listeleniyor');
    }

    public function test_invoice_with_collection_links_to_collections(): void
    {
        $invoice = FinanceInvoiceDummyData::find(1);

        $this->assertNotNull($invoice['collection_id']);
        $this->assertGreaterThan(0, $invoice['collected_amount']);
    }

    public function test_invoice_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.invoices.show', 9999));

        $response->assertNotFound();
    }
}
