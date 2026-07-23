<?php

namespace Tests\Feature;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Services\CurrentAccountPresenter;
use App\Modules\Finance\Services\CurrentAccountService;
use App\Modules\Finance\Services\PaymentService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCurrentAccountTest extends TestCase
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

    public function test_current_accounts_index_redirects_to_business_cari(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('finance.current-accounts.index'))
            ->assertRedirect(route('finance.current-accounts.business'));
    }

    public function test_business_cari_lists_only_business_accounts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'company_name' => 'Burger House Gıda Ltd. Şti.',
            'brand_name' => 'Burger House',
        ]);
        $courier = Courier::factory()->create(['full_name' => 'Ahmet Yıldız']);
        $agency = Agency::factory()->create(['company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.']);

        app(CurrentAccountService::class)->ensureForEntity($business);
        app(CurrentAccountService::class)->ensureForEntity($courier);
        app(CurrentAccountService::class)->ensureForEntity($agency);

        $response = $this->actingAs($user)->get(route('finance.current-accounts.business'));

        $response->assertOk();
        $response->assertSee('İşletme Cari');
        $response->assertSee('Ödeme Alındı');
        $response->assertSee('Toplam Alacak');
        $response->assertSee('Burger House');
        $response->assertSee('Burger House Gıda Ltd. Şti.');
        $response->assertDontSee('Ahmet Yıldız');
        $response->assertDontSee('Hızlı Kurye Acentesi Ltd. Şti.');
    }

    public function test_courier_cari_lists_only_courier_accounts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('general_manager');

        $business = Business::factory()->create(['company_name' => 'Sadece İşletme Ltd.']);
        $courier = Courier::factory()->create(['full_name' => 'Kurye Cari Test']);

        app(CurrentAccountService::class)->ensureForEntity($business);
        app(CurrentAccountService::class)->ensureForEntity($courier);

        $response = $this->actingAs($user)->get(route('finance.current-accounts.courier'));

        $response->assertOk();
        $response->assertSee('Kurye Cari');
        $response->assertSee('Ödeme Yapıldı');
        $response->assertSee('Toplam Borç');
        $response->assertSee('Kurye Cari Test');
        $response->assertDontSee('Sadece İşletme Ltd.');
    }

    public function test_operations_specialist_cannot_view_cari_pages(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $this->actingAs($user)
            ->get(route('finance.current-accounts.business'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('finance.current-accounts.courier'))
            ->assertForbidden();
    }

    public function test_entity_accounts_are_synced_on_business_index(): void
    {
        Business::factory()->count(2)->create();
        Courier::factory()->count(2)->create();
        Agency::factory()->count(1)->create();

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get(route('finance.current-accounts.business'));

        $this->assertDatabaseCount('current_accounts', 5);
    }

    public function test_user_can_create_collection_movement_on_business_cari(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['company_name' => 'Hareket Test İşletmesi']);
        $account = app(CurrentAccountService::class)->ensureForEntity($business);

        // Açık tahsilat kaydı (cari hareketi fatura/gelirden gelir; burada alacağı manuel invoice ile kuruyoruz)
        app(\App\Modules\Finance\Services\InvoiceService::class)->create([
            'business_id' => $business->id,
            'invoice_type' => 'manual',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 5000,
            'vat_rate' => 20,
            'description' => 'Cari tahsilat testi',
        ], $user);

        $response = $this->actingAs($user)->post(route('finance.current-accounts.movements.store'), [
            'current_account_id' => $account->id,
            'transaction_date' => now()->toDateString(),
            'type' => 'collection',
            'document_no' => 'THS-2026-0001',
            'amount' => 5000,
            'description' => 'Test tahsilat',
        ]);

        $response->assertRedirect(route('finance.current-accounts.business'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('current_account_movements', [
            'current_account_id' => $account->id,
            'type' => 'collection',
            'credit' => 5000,
            'debit' => 0,
        ]);

        $balance = round(
            (float) $account->movements()->sum('debit') - (float) $account->movements()->sum('credit'),
            2
        );
        $this->assertSame(0.0, $balance);
    }

    public function test_courier_cari_payment_applies_to_open_finance_payments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create(['full_name' => 'Cari Ödeme Test']);
        $account = app(CurrentAccountService::class)->ensureForEntity($courier);

        $line = EarningLine::factory()->create([
            'courier_id' => $courier->id,
            'created_by' => $user->id,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
            'net_courier_payment' => 1500,
        ]);

        $payment = app(PaymentService::class)->create([
            'recipient_type' => 'courier',
            'recipient_id' => $courier->id,
            'earning_line_id' => $line->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 1500,
            'paid_amount' => 0,
        ], $user);

        $this->actingAs($user)->post(route('finance.current-accounts.movements.store'), [
            'current_account_id' => $account->id,
            'transaction_date' => now()->toDateString(),
            'type' => 'payment',
            'document_no' => 'CARI-PAY-1',
            'amount' => 1500,
            'description' => 'Cari üzerinden ödeme',
        ])->assertRedirect(route('finance.current-accounts.courier'))
            ->assertSessionHas('success');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertEquals(1500.0, (float) $payment->fresh()->paid_amount);

        $balance = round(
            (float) $account->movements()->sum('debit') - (float) $account->movements()->sum('credit'),
            2
        );
        $this->assertSame(0.0, $balance);
    }

    public function test_earning_payment_posts_courier_liability_and_payment_reduces_it(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create();
        $account = app(CurrentAccountService::class)->ensureForEntity($courier);

        $line = EarningLine::factory()->create([
            'courier_id' => $courier->id,
            'created_by' => $user->id,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
            'net_courier_payment' => 900,
        ]);

        $payment = app(PaymentService::class)->create([
            'recipient_type' => 'courier',
            'recipient_id' => $courier->id,
            'earning_line_id' => $line->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 900,
            'paid_amount' => 0,
            'description' => 'Test kurye hakediş borcu',
        ], $user);

        $this->assertDatabaseHas('current_account_movements', [
            'current_account_id' => $account->id,
            'type' => 'earning',
            'credit' => 900,
            'related_type' => FinancePayment::class,
            'related_id' => $payment->id,
        ]);

        $balanceAfterLiability = round(
            (float) $account->movements()->sum('debit') - (float) $account->movements()->sum('credit'),
            2
        );
        $this->assertSame(-900.0, $balanceAfterLiability);

        $this->actingAs($user)->post(route('finance.payments.bulk'), [
            'ids' => [$payment->id],
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ])->assertRedirect();

        $balance = round(
            (float) $account->movements()->sum('debit') - (float) $account->movements()->sum('credit'),
            2
        );
        $this->assertSame(0.0, $balance);
    }

    public function test_courier_summary_net_balance_is_positive_debt(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create(['full_name' => 'Net Borç Test']);
        $account = app(CurrentAccountService::class)->ensureForEntity($courier);

        app(PaymentService::class)->create([
            'recipient_type' => 'courier',
            'recipient_id' => $courier->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 2_500,
            'paid_amount' => 0,
        ], $user);

        $summary = app(CurrentAccountService::class)->summarize([
            'type' => 'courier',
            'status' => 'all',
            'balance_status' => 'all',
            'search' => '',
        ]);

        $this->assertEquals(2_500.0, $summary['total_payable']);
        $this->assertEquals(2_500.0, $summary['net_balance']);

        $row = app(CurrentAccountPresenter::class)->indexRow($account->fresh(['movements']));
        $this->assertEquals(-2_500.0, $row['balance']);
        $this->assertSame('2.500,00 ₺', $row['balance_formatted']);
    }

    public function test_backfill_earning_liabilities_is_idempotent(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create();
        $account = app(CurrentAccountService::class)->ensureForEntity($courier);

        $payment = FinancePayment::factory()->forCourier($courier)->create([
            'current_account_id' => $account->id,
            'source' => 'earning',
            'total_amount' => 500,
            'paid_amount' => 0,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $service = app(PaymentService::class);
        $this->assertSame(1, $service->backfillEarningLiabilities($user));
        $this->assertSame(0, $service->backfillEarningLiabilities($user));

        $this->assertSame(1, CurrentAccountMovement::query()
            ->where('related_type', FinancePayment::class)
            ->where('related_id', $payment->id)
            ->where('type', 'earning')
            ->count());
    }

    public function test_earning_liability_stores_full_related_type_class_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create();
        $account = app(CurrentAccountService::class)->ensureForEntity($courier);

        $payment = FinancePayment::factory()->forCourier($courier)->create([
            'current_account_id' => $account->id,
            'source' => 'earning',
            'total_amount' => 250,
            'paid_amount' => 0,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        app(PaymentService::class)->ensureEarningLiability($payment, $user);

        $relatedType = CurrentAccountMovement::query()
            ->where('related_id', $payment->id)
            ->where('type', 'earning')
            ->value('related_type');

        $this->assertSame(FinancePayment::class, $relatedType);
        $this->assertGreaterThan(30, strlen((string) $relatedType));
    }

    public function test_ensure_earning_liability_skips_missing_current_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create();

        $payment = FinancePayment::factory()->forCourier($courier)->make([
            'current_account_id' => 999999,
            'source' => 'earning',
            'total_amount' => 100,
            'paid_amount' => 0,
            'status' => 'pending',
            'is_active' => true,
            'reference' => 'ODM-2026-ORPHAN',
            'created_by' => $user->id,
        ]);
        $payment->id = 999999;

        app(PaymentService::class)->ensureEarningLiability($payment, $user);

        $this->assertDatabaseCount('current_account_movements', 0);
    }
}
