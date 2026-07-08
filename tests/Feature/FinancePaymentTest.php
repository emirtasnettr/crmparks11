<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinancePaymentDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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

        $response = $this->actingAs($user)->get(route('finance.payments.show', 16));

        $response->assertOk();
        $response->assertSee('Ödeme Detayı');
        $response->assertSee('ODM-2026-000016');
        $response->assertSee('Alıcı Bilgileri');
        $response->assertSee('Hakediş Bilgileri');
        $response->assertSee('Cari Hareketi');
        $response->assertSee('Ödeme Bilgileri');
        $response->assertSee('Dekontlar');
        $response->assertSee('Notlar');
    }

    public function test_dummy_data_has_seventy_five_payment_records_with_mixed_statuses(): void
    {
        $payments = FinancePaymentDummyData::all();

        $this->assertGreaterThanOrEqual(70, count($payments));
        $this->assertCount(75, $payments);
        $this->assertGreaterThan(0, collect($payments)->where('status', 'paid')->count());
        $this->assertGreaterThan(0, collect($payments)->where('status', 'partial')->count());
        $this->assertGreaterThan(0, collect($payments)->where('status', 'pending')->count());
        $this->assertGreaterThan(0, collect($payments)->where('status', 'cancelled')->count());
        $this->assertGreaterThan(0, collect($payments)->where('recipient_type', 'courier')->count());
        $this->assertGreaterThan(0, collect($payments)->where('recipient_type', 'agency')->count());
        $this->assertGreaterThan(0, collect($payments)->where('recipient_type', 'personnel')->count());
        $this->assertGreaterThan(0, collect($payments)->where('source', 'manual')->count());
    }

    public function test_remaining_amount_is_calculated_correctly(): void
    {
        $payment = FinancePaymentDummyData::find(16);

        $this->assertNotNull($payment);
        $this->assertEquals(
            round($payment['total_amount'] - $payment['paid_amount'], 2),
            $payment['remaining_amount']
        );
    }

    public function test_payments_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.payments.index', [
            'payment_status' => 'partial',
        ]));

        $response->assertOk();
        $response->assertSee('Kısmi Ödendi');
        $response->assertSee('ödeme kaydı listeleniyor');
    }

    public function test_partial_payment_has_payment_history(): void
    {
        $payment = FinancePaymentDummyData::find(23);

        $this->assertEquals('partial', $payment['status']);
        $this->assertGreaterThanOrEqual(2, count($payment['payment_history']));
    }

    public function test_cancelled_payment_is_marked_inactive(): void
    {
        $payment = FinancePaymentDummyData::find(66);

        $this->assertEquals('cancelled', $payment['status']);
        $this->assertFalse($payment['is_active']);
    }

    public function test_payment_show_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.payments.show', 9999));

        $response->assertNotFound();
    }
}
