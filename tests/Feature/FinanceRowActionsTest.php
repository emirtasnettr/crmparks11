<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceRowActionsTest extends TestCase
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

    public function test_invoice_can_be_cancelled(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $account = CurrentAccount::factory()->create(['status' => 'active']);
        $invoice = FinanceInvoice::factory()->create([
            'current_account_id' => $account->id,
            'invoice_status' => 'issued',
            'collected_amount' => 0,
            'subtotal' => 1000,
            'created_by' => $user->id,
        ]);

        CurrentAccountMovement::query()->create([
            'current_account_id' => $account->id,
            'transaction_date' => now()->toDateString(),
            'type' => 'invoice',
            'document_no' => $invoice->reference,
            'debit' => 1000,
            'credit' => 0,
            'description' => 'Fatura: '.$invoice->reference,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('finance.invoices.cancel', $invoice->id))
            ->assertRedirect(route('finance.invoices.index'));

        $this->assertSame('cancelled', $invoice->fresh()->invoice_status);
        $this->assertDatabaseHas('current_account_movements', [
            'current_account_id' => $account->id,
            'type' => 'credit_note',
            'document_no' => $invoice->reference,
            'credit' => 1000,
        ]);
    }

    public function test_collected_invoice_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $invoice = FinanceInvoice::factory()->create([
            'invoice_status' => 'issued',
            'collected_amount' => 500,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('finance.invoices.cancel', $invoice->id))
            ->assertSessionHasErrors('invoice');

        $this->assertSame('issued', $invoice->fresh()->invoice_status);
    }

    public function test_current_account_can_be_deactivated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $account = CurrentAccount::factory()->business()->create(['status' => 'active']);

        $this->actingAs($user)
            ->post(route('finance.current-accounts.deactivate', $account->id))
            ->assertRedirect(route('finance.current-accounts.business'));

        $this->assertSame('passive', $account->fresh()->status);
    }

    public function test_expense_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $expense = FinanceExpense::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('finance.expenses.destroy', $expense->id))
            ->assertRedirect(route('finance.expenses.index'));

        $this->assertDatabaseMissing('finance_expenses', ['id' => $expense->id]);
    }

    public function test_collection_receipt_can_be_uploaded_and_downloaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $collection = FinanceCollection::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->create('dekont.pdf', 120, 'application/pdf');

        $this->actingAs($user)
            ->post(route('finance.collections.receipts.store', $collection->id), [
                'file' => $file,
            ])
            ->assertRedirect(route('finance.collections.show', $collection->id));

        $collection->refresh();
        $this->assertNotNull($collection->receipt_path);
        $this->assertSame('dekont.pdf', $collection->receipt_original_name);
        Storage::disk('public')->assertExists($collection->receipt_path);

        $this->actingAs($user)
            ->get(route('finance.collections.receipts.download', $collection->id))
            ->assertOk();
    }
}
