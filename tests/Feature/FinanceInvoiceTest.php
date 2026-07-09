<?php

namespace Tests\Feature;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Services\InvoicePresenter;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceInvoiceTest extends TestCase
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

    public function test_invoices_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.invoices.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_invoices_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceInvoice::factory()->partialCollection()->create();
        FinanceInvoice::factory()->overdue()->create();

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

        $invoice = FinanceInvoice::factory()
            ->forEarning()
            ->collected()
            ->create([
                'invoice_type' => 'e_invoice',
                'notes' => 'Muhasebe onayı tamamlandı.',
            ]);

        $response = $this->actingAs($user)->get(route('finance.invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertSee('Fatura Detayı');
        $response->assertSee($invoice->reference);
        $response->assertSee('Fatura Bilgileri');
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Hakediş Bilgileri');
        $response->assertSee('Tahsilat Bilgileri');
        $response->assertSee('Cari Hareketleri');
        $response->assertSee('PDF Önizleme');
    }

    public function test_grand_total_is_calculated_correctly(): void
    {
        $invoice = FinanceInvoice::factory()->create([
            'subtotal' => 10000,
            'vat_rate' => 20,
            'vat_amount' => 2000,
            'grand_total' => 12000,
        ]);

        $presented = app(InvoicePresenter::class)->showRow($invoice->fresh(['business.city', 'business.district', 'earningLine', 'currentAccount', 'collection']));

        $this->assertEquals(
            round((float) $invoice->subtotal + (float) $invoice->vat_amount, 2),
            $presented['grand_total']
        );
    }

    public function test_each_earning_has_at_most_one_invoice(): void
    {
        $earning = EarningLine::factory()->create();
        FinanceInvoice::factory()->forEarning($earning)->create();

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->post(route('finance.invoices.store'), [
            'business_id' => $earning->business_id,
            'earning_line_id' => $earning->id,
            'invoice_date' => '2026-07-09',
            'due_date' => '2026-07-24',
            'subtotal' => 10000,
            'vat_rate' => 20,
        ]);

        $response->assertSessionHasErrors('earning_line_id');
    }

    public function test_invoices_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceInvoice::factory()->create(['invoice_type' => 'e_invoice']);
        FinanceInvoice::factory()->create(['invoice_type' => 'manual']);

        $response = $this->actingAs($user)->get(route('finance.invoices.index', [
            'invoice_type' => 'e_invoice',
        ]));

        $response->assertOk();
        $response->assertSee('fatura kaydı listeleniyor');
    }

    public function test_invoice_with_collection_links_to_collections(): void
    {
        $invoice = FinanceInvoice::factory()->collected()->create();

        $this->assertNotNull($invoice->collection_id);
        $this->assertGreaterThan(0, (float) $invoice->collected_amount);
    }

    public function test_invoice_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.invoices.show', 9999));

        $response->assertNotFound();
    }

    public function test_user_can_create_invoice_with_collection_and_current_account_movement(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'company_name' => 'Test İşletme A.Ş.',
            'brand_name' => 'Test Marka',
        ]);

        $response = $this->actingAs($user)->post(route('finance.invoices.store'), [
            'business_id' => $business->id,
            'invoice_type' => 'manual',
            'invoice_date' => '2026-07-09',
            'due_date' => '2026-07-24',
            'subtotal' => 25000,
            'vat_rate' => 20,
            'description' => 'Manuel fatura testi',
        ]);

        $response->assertRedirect(route('finance.invoices.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_invoices', [
            'business_id' => $business->id,
            'subtotal' => 25000,
            'vat_amount' => 5000,
            'grand_total' => 30000,
            'invoice_status' => 'issued',
            'collection_status' => 'pending',
        ]);

        $invoice = FinanceInvoice::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->collection_id);

        $this->assertDatabaseHas('finance_collections', [
            'id' => $invoice->collection_id,
            'business_id' => $business->id,
            'total_amount' => 25000,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('current_accounts', [
            'accountable_type' => Business::class,
            'accountable_id' => $business->id,
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'type' => 'invoice',
            'debit' => 25000,
        ]);
    }

    public function test_user_can_create_invoice_linked_to_earning(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $earning = EarningLine::factory()->create(['revenue_total' => 18500]);

        $response = $this->actingAs($user)->post(route('finance.invoices.store'), [
            'business_id' => $earning->business_id,
            'earning_line_id' => $earning->id,
            'invoice_type' => 'e_invoice',
            'invoice_date' => '2026-07-09',
            'due_date' => '2026-07-24',
            'subtotal' => 18500,
            'vat_rate' => 20,
        ]);

        $response->assertRedirect(route('finance.invoices.index'));

        $this->assertDatabaseHas('finance_invoices', [
            'business_id' => $earning->business_id,
            'earning_line_id' => $earning->id,
            'source' => 'earning',
            'subtotal' => 18500,
            'invoice_type' => 'e_invoice',
        ]);
    }
}
