<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceInvoice;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfExportTest extends TestCase
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

    public function test_invoice_pdf_streams_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $invoice = FinanceInvoice::factory()->create([
            'business_id' => Business::factory()->create()->id,
            'invoice_status' => 'issued',
        ]);

        $response = $this->actingAs($user)->get(route('finance.invoices.pdf', $invoice->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_list_pdf_downloads_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinanceInvoice::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('finance.invoices.export-pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_revenue_list_pdf_requires_financial_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_staff');

        $this->actingAs($user)
            ->get(route('finance.revenues.export-pdf'))
            ->assertForbidden();
    }
}
