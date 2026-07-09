<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Services\PaymentPresenter;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancePaymentTest extends TestCase
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

    public function test_payments_index_requires_authentication(): void
    {
        $response = $this->get(route('finance.payments.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_payments_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinancePayment::factory()->partial()->forCourier()->create();
        FinancePayment::factory()->forAgency()->create();

        $response = $this->actingAs($user)->get(route('finance.payments.index'));

        $response->assertOk();
        $response->assertSee('Ödemeler');
        $response->assertSee('Kurye, acente ve diğer cari hesaplara yapılan ödemeleri yönetin.');
        $response->assertSee('Yeni Ödeme');
        $response->assertSee('Toplu Ödeme');
        $response->assertSee('Toplam Ödeme');
        $response->assertSee('Bu Ay Yapılan Ödeme');
        $response->assertSee('Bekleyen Ödemeler');
        $response->assertSee('Bugün Yapılan Ödeme');
        $response->assertSee('Kurye Ödemeleri');
        $response->assertSee('Acente Ödemeleri');
        $response->assertSee('Kısmi Ödendi');
        $response->assertSee('ödeme kaydı listeleniyor');
    }

    public function test_user_without_financial_permission_cannot_view_payments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.payments.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_payment_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $payment = FinancePayment::factory()
            ->forCourier()
            ->paid()
            ->create([
                'notes' => 'Finans departmanı tarafından onaylandı.',
            ]);

        $response = $this->actingAs($user)->get(route('finance.payments.show', $payment->id));

        $response->assertOk();
        $response->assertSee('Ödeme Detayı');
        $response->assertSee($payment->reference);
        $response->assertSee('Alıcı Bilgileri');
        $response->assertSee('Hakediş Bilgileri');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Ödeme Bilgileri');
        $response->assertSee('Dekontlar');
        $response->assertSee('Notlar');
        $response->assertSee('Finans departmanı tarafından onaylandı.');
    }

    public function test_remaining_amount_is_calculated_correctly(): void
    {
        $payment = FinancePayment::factory()->partial()->forCourier()->create();
        $presented = app(PaymentPresenter::class)->showRow($payment->fresh(['courier', 'agency', 'earningLine', 'currentAccount', 'lines']));

        $this->assertEquals(
            round((float) $payment->total_amount - (float) $payment->paid_amount, 2),
            $presented['remaining_amount']
        );
    }

    public function test_payments_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinancePayment::factory()->partial()->forCourier()->create();
        FinancePayment::factory()->paid()->forCourier()->create();

        $response = $this->actingAs($user)->get(route('finance.payments.index', [
            'payment_status' => 'partial',
        ]));

        $response->assertOk();
        $response->assertSee('Kısmi Ödendi');
        $response->assertSee('ödeme kaydı listeleniyor');
    }

    public function test_partial_payment_has_payment_history(): void
    {
        $payment = FinancePayment::factory()->partial()->forCourier()->create()->fresh(['lines']);
        $presented = app(PaymentPresenter::class)->showRow($payment);

        $this->assertEquals('partial', $payment->status);
        $this->assertGreaterThanOrEqual(2, count($presented['payment_history']));
    }

    public function test_cancelled_payment_is_marked_inactive(): void
    {
        $payment = FinancePayment::factory()->cancelled()->forCourier()->create();

        $this->assertEquals('cancelled', $payment->status);
        $this->assertFalse($payment->is_active);
    }

    public function test_payment_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.payments.show', 9999));

        $response->assertNotFound();
    }

    public function test_user_can_create_courier_payment_with_current_account_movement(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $courier = Courier::factory()->create(['full_name' => 'Test Kurye']);

        $response = $this->actingAs($user)->post(route('finance.payments.store'), [
            'recipient_type' => 'courier',
            'recipient_id' => $courier->id,
            'payment_date' => '2026-07-09',
            'total_amount' => 15000,
            'paid_amount' => 15000,
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'PAY-2026-00099',
            'bank_account' => 'Garanti BBVA — TR1000000000000001',
            'description' => 'Kurye hakediş ödemesi',
        ]);

        $response->assertRedirect(route('finance.payments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_payments', [
            'recipient_type' => 'courier',
            'courier_id' => $courier->id,
            'total_amount' => 15000,
            'paid_amount' => 15000,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('finance_payment_lines', [
            'amount' => 15000,
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertDatabaseHas('current_accounts', [
            'accountable_type' => Courier::class,
            'accountable_id' => $courier->id,
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'type' => 'payment',
            'debit' => 15000,
        ]);
    }

    public function test_user_can_create_pending_agency_payment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $agency = Agency::factory()->create(['company_name' => 'Test Acente Ltd.']);

        $response = $this->actingAs($user)->post(route('finance.payments.store'), [
            'recipient_type' => 'agency',
            'recipient_id' => $agency->id,
            'payment_date' => '2026-07-15',
            'total_amount' => 9500,
            'paid_amount' => 0,
        ]);

        $response->assertRedirect(route('finance.payments.index'));

        $this->assertDatabaseHas('finance_payments', [
            'recipient_type' => 'agency',
            'agency_id' => $agency->id,
            'total_amount' => 9500,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);
    }
}
