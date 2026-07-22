<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceUpdateTest extends TestCase
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

    public function test_user_can_update_revenue(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create();
        $revenue = FinanceRevenue::factory()->for($business)->create([
            'amount' => 10000,
            'description' => 'Eski açıklama',
        ]);

        $response = $this->actingAs($user)->put(route('finance.revenues.update', $revenue->id), [
            'business_id' => $business->id,
            'revenue_type' => 'manual',
            'period_label' => 'Temmuz 2026',
            'revenue_date' => '2026-07-10',
            'amount' => 15000,
            'vat_rate' => 20,
            'description' => 'Güncellenmiş gelir',
            'collection_status' => 'pending',
        ]);

        $response->assertRedirect(route('finance.revenues.show', $revenue->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_revenues', [
            'id' => $revenue->id,
            'amount' => 15000,
            'description' => 'Güncellenmiş gelir',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'revenue_updated',
            'subject_type' => FinanceRevenue::class,
            'subject_id' => $revenue->id,
        ]);
    }

    public function test_user_can_update_expense(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $expense = FinanceExpense::factory()->create([
            'amount' => 500,
            'description' => 'Eski gider',
        ]);

        $response = $this->actingAs($user)->put(route('finance.expenses.update', $expense->id), [
            'expense_type' => 'office',
            'expense_date' => '2026-07-10',
            'amount' => 750,
            'vat_rate' => 20,
            'description' => 'Güncellenmiş gider',
            'payment_status' => 'pending',
        ]);

        $response->assertRedirect(route('finance.expenses.show', $expense->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_expenses', [
            'id' => $expense->id,
            'amount' => 750,
            'description' => 'Güncellenmiş gider',
        ]);
    }

    public function test_user_can_update_collection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create();
        $collection = FinanceCollection::factory()->for($business)->create([
            'total_amount' => 5000,
            'collected_amount' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->put(route('finance.collections.update', $collection->id), [
            'business_id' => $business->id,
            'due_date' => '2026-07-20',
            'total_amount' => 6000,
            'description' => 'Güncellenmiş tahsilat',
        ]);

        $response->assertRedirect(route('finance.collections.show', $collection->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_collections', [
            'id' => $collection->id,
            'total_amount' => 6000,
            'description' => 'Güncellenmiş tahsilat',
        ]);
    }

    public function test_fully_collected_collection_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create();
        $collection = FinanceCollection::factory()->for($business)->create([
            'total_amount' => 5000,
            'collected_amount' => 5000,
            'status' => 'collected',
        ]);

        $response = $this->actingAs($user)->put(route('finance.collections.update', $collection->id), [
            'business_id' => $business->id,
            'due_date' => '2026-07-20',
            'total_amount' => 6000,
        ]);

        $response->assertSessionHasErrors('collection');
    }

    public function test_user_can_update_payment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create();
        $payment = FinancePayment::factory()->forCourier($courier)->create([
            'total_amount' => 3000,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->put(route('finance.payments.update', $payment->id), [
            'recipient_type' => 'courier',
            'recipient_id' => $courier->id,
            'payment_date' => '2026-07-10',
            'total_amount' => 3500,
            'description' => 'Güncellenmiş ödeme',
        ]);

        $response->assertRedirect(route('finance.payments.show', $payment->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_payments', [
            'id' => $payment->id,
            'total_amount' => 3500,
            'description' => 'Güncellenmiş ödeme',
        ]);
    }

    public function test_paid_payment_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create();
        $payment = FinancePayment::factory()->forCourier($courier)->create([
            'total_amount' => 3000,
            'paid_amount' => 3000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($user)->put(route('finance.payments.update', $payment->id), [
            'recipient_type' => 'courier',
            'recipient_id' => $courier->id,
            'payment_date' => '2026-07-10',
            'total_amount' => 3500,
        ]);

        $response->assertSessionHasErrors('payment');
    }

    public function test_user_can_update_invoice(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create();
        $invoice = FinanceInvoice::factory()->for($business)->create([
            'subtotal' => 10000,
            'description' => 'Eski fatura',
        ]);

        $response = $this->actingAs($user)->put(route('finance.invoices.update', $invoice->id), [
            'business_id' => $business->id,
            'invoice_type' => 'e_invoice',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-25',
            'subtotal' => 12000,
            'vat_rate' => 20,
            'description' => 'Güncellenmiş fatura',
        ]);

        $response->assertRedirect(route('finance.invoices.show', $invoice->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_invoices', [
            'id' => $invoice->id,
            'subtotal' => 12000,
            'description' => 'Güncellenmiş fatura',
        ]);
    }

    public function test_user_can_update_current_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $account = CurrentAccount::factory()->business()->create([
            'title' => 'Eski Ünvan',
            'phone' => '5551112233',
        ]);

        $response = $this->actingAs($user)->put(route('finance.current-accounts.update', $account->id), [
            'type' => $account->account_type,
            'title' => 'Yeni Ünvan Ltd.',
            'phone' => '5559998877',
            'email' => 'yeni@ornek.com',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('finance.current-accounts.business'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('current_accounts', [
            'id' => $account->id,
            'title' => 'Yeni Ünvan Ltd.',
            'phone' => '5559998877',
        ]);
    }
}
