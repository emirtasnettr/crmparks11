<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Services\BusinessEarningService;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\Setting\Services\SettingsManager;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEarningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            CitySeeder::class,
            RoleAndPermissionSeeder::class,
        ]);

        $this->setApprovalProcess('single');
    }

    public function test_super_admin_can_update_earning(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user);

        $response = $this->actingAs($user)->put(route('businesses.earnings.update', $line->id), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-10',
            'pricing_model' => 'per_package',
            'package_count' => 120,
            'revenue_unit_price' => 50,
            'courier_unit_price' => 40,
            'extra_income' => 0,
            'extra_expense' => 0,
            'deduction' => 0,
            'description' => 'Güncellendi',
        ]);

        $response->assertRedirect(route('businesses.earnings.show', $line->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('earning_lines', [
            'id' => $line->id,
            'package_count' => 120,
            'period_month' => 7,
            'period_year' => 2026,
            'description' => 'Güncellendi',
        ]);

        $line->refresh();
        $this->assertSame('2026-07-10', $line->work_date?->toDateString());

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'earning_updated',
            'subject_type' => EarningLine::class,
            'subject_id' => $line->id,
        ]);
    }

    public function test_general_manager_can_approve_pending_earning(): void
    {
        $user = User::factory()->create();
        $user->assignRole('general_manager');
        $financeOfficer = User::factory()->create();
        $financeOfficer->assignRole('general_manager');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review');

        $response = $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id));

        $response->assertRedirect(route('businesses.earnings.show', $line->id));
        $response->assertSessionHas('success');

        $line->refresh();
        $this->assertSame('approved', $line->status?->code);
        $this->assertSame($user->id, $line->approved_by);
        $this->assertNotNull($line->approved_at);

        $this->assertTrue(
            $financeOfficer->notifications()->where('data->type', 'earning_approved')->exists()
        );

        $this->assertDatabaseHas('finance_revenues', [
            'earning_line_id' => $line->id,
            'business_id' => $business->id,
            'revenue_type' => 'per_package',
            'collection_status' => 'pending',
        ]);

        $this->assertDatabaseHas('finance_payments', [
            'earning_line_id' => $line->id,
            'recipient_type' => 'courier',
            'recipient_id' => $courier->id,
            'source' => 'earning',
        ]);

        $this->assertSame(1, FinanceRevenue::query()->where('earning_line_id', $line->id)->count());
        $this->assertSame(1, FinancePayment::query()->where('earning_line_id', $line->id)->count());
    }

    public function test_approve_earning_posts_business_receivable_on_cari(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review');

        $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id))
            ->assertRedirect();

        $revenue = FinanceRevenue::query()->where('earning_line_id', $line->id)->first();
        $this->assertNotNull($revenue);

        $account = CurrentAccount::query()
            ->where('accountable_type', Business::class)
            ->where('accountable_id', $business->id)
            ->first();

        $this->assertNotNull($account);
        $this->assertDatabaseHas('current_account_movements', [
            'current_account_id' => $account->id,
            'related_type' => FinanceRevenue::class,
            'related_id' => $revenue->id,
            'type' => 'invoice',
            'debit' => $revenue->amount,
        ]);
    }

    public function test_paying_courier_finance_payment_marks_earning_paid(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review');

        $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id))->assertRedirect();

        $payment = FinancePayment::query()
            ->where('earning_line_id', $line->id)
            ->where('recipient_type', 'courier')
            ->first();
        $this->assertNotNull($payment);

        $this->actingAs($user)->post(route('finance.payments.bulk'), [
            'ids' => [$payment->id],
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ])->assertRedirect();

        $line->refresh()->load('status');
        $this->assertSame('paid', $line->status?->code);
        $this->assertNotNull($line->paid_at);
    }

    public function test_approve_earning_with_agency_payment_creates_agency_finance_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $agency = Agency::factory()->create(['created_by' => $user->id]);
        $courier = $this->createCourier($user, ['agency_id' => $agency->id]);
        $line = $this->createEarning($business, $courier, $user, 'pending_review', [
            'agency_payment' => 2500.00,
        ]);

        $response = $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id));

        $response->assertRedirect(route('businesses.earnings.show', $line->id));

        $this->assertDatabaseHas('finance_payments', [
            'earning_line_id' => $line->id,
            'recipient_type' => 'agency',
            'recipient_id' => $agency->id,
            'total_amount' => 2500.00,
        ]);

        $this->assertSame(2, FinancePayment::query()->where('earning_line_id', $line->id)->count());
    }

    public function test_approve_earning_with_extra_expense_creates_expense_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review', [
            'extra_expense' => 750.50,
        ]);

        $response = $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id));

        $response->assertRedirect(route('businesses.earnings.show', $line->id));

        $this->assertDatabaseHas('finance_expenses', [
            'earning_line_id' => $line->id,
            'expense_type' => 'other',
            'amount' => 750.50,
            'payment_status' => 'pending',
        ]);

        $this->assertSame(1, FinanceExpense::query()->where('earning_line_id', $line->id)->count());
    }

    public function test_user_without_approve_permission_cannot_approve_earning(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('earning.update');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review');

        $response = $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id));

        $response->assertForbidden();
    }

    public function test_super_admin_can_delete_draft_earning(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user);

        $response = $this->actingAs($user)->delete(route('businesses.earnings.destroy', $line->id));

        $response->assertRedirect(route('businesses.earnings.index', [
            'business_id' => $business->id,
            'period_month' => $line->period_month,
            'period_year' => $line->period_year,
        ]));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('earning_lines', ['id' => $line->id]);
    }

    public function test_deleting_approved_earning_reverses_finance_and_cari(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review');

        $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id))
            ->assertRedirect();

        $revenue = FinanceRevenue::query()->where('earning_line_id', $line->id)->first();
        $payment = FinancePayment::query()->where('earning_line_id', $line->id)->first();
        $this->assertNotNull($revenue);
        $this->assertNotNull($payment);

        $businessAccount = CurrentAccount::query()
            ->where('accountable_type', Business::class)
            ->where('accountable_id', $business->id)
            ->first();
        $courierAccount = CurrentAccount::query()
            ->where('accountable_type', Courier::class)
            ->where('accountable_id', $courier->id)
            ->first();

        $this->assertNotNull($businessAccount);
        $this->assertNotNull($courierAccount);

        $this->actingAs($user)->delete(route('businesses.earnings.destroy', $line->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('earning_lines', ['id' => $line->id]);

        $revenue->refresh();
        $payment->refresh();

        $this->assertSame('cancelled', $revenue->collection_status);
        $this->assertNull($revenue->earning_line_id);
        $this->assertFalse($payment->is_active);
        $this->assertSame('cancelled', $payment->status);
        $this->assertNull($payment->earning_line_id);

        $this->assertDatabaseHas('current_account_movements', [
            'current_account_id' => $businessAccount->id,
            'related_type' => FinanceRevenue::class,
            'related_id' => $revenue->id,
            'type' => 'credit_note',
            'credit' => $revenue->amount,
        ]);

        $this->assertDatabaseHas('current_account_movements', [
            'current_account_id' => $courierAccount->id,
            'related_type' => FinancePayment::class,
            'related_id' => $payment->id,
            'type' => 'debit_note',
            'debit' => $payment->total_amount,
        ]);
    }

    public function test_paid_finance_payment_blocks_earning_delete(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review');

        $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id))
            ->assertRedirect();

        $payment = FinancePayment::query()->where('earning_line_id', $line->id)->firstOrFail();

        $this->actingAs($user)->post(route('finance.payments.bulk'), [
            'ids' => [$payment->id],
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ])->assertRedirect();

        $line->refresh()->load('status');
        $this->assertSame('paid', $line->status?->code);

        $this->actingAs($user)->delete(route('businesses.earnings.destroy', $line->id))
            ->assertSessionHasErrors('earning');

        $this->assertNotSoftDeleted('earning_lines', ['id' => $line->id]);
    }

    public function test_paid_earning_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'paid');

        $response = $this->actingAs($user)->put(route('businesses.earnings.update', $line->id), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-10',
            'pricing_model' => 'per_package',
            'package_count' => 50,
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
        ]);

        $response->assertSessionHasErrors('earning');
    }

    public function test_approved_earning_cannot_be_reapproved(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'approved');

        $response = $this->actingAs($user)->post(route('businesses.earnings.approve', $line->id));

        $response->assertSessionHasErrors('earning');
    }

    public function test_create_earning_writes_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-08-05',
            'pricing_model' => 'per_package',
            'package_count' => 80,
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
        ]);

        $line = EarningLine::query()->first();

        $this->assertNotNull($line);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'earning_created',
            'subject_type' => EarningLine::class,
            'subject_id' => $line->id,
        ]);
    }

    public function test_dual_approval_requires_two_different_users(): void
    {
        $this->setApprovalProcess('dual');

        $first = User::factory()->create();
        $first->assignRole('general_manager');
        $second = User::factory()->create();
        $second->assignRole('super_admin');
        $business = $this->createBusiness($first);
        $courier = $this->createCourier($first);
        $line = $this->createEarning($business, $courier, $first, 'pending_review');

        $firstResponse = $this->actingAs($first)->post(route('businesses.earnings.approve', $line->id));
        $firstResponse->assertRedirect(route('businesses.earnings.show', $line->id));
        $firstResponse->assertSessionHas('success', 'Hakediş ilk onayı alındı. İkinci onay bekleniyor.');

        $line->refresh();
        $this->assertSame('pending_review', $line->status?->code);
        $this->assertSame($first->id, $line->first_approved_by);
        $this->assertNotNull($line->first_approved_at);
        $this->assertNull($line->approved_by);
        $this->assertSame(0, FinanceRevenue::query()->where('earning_line_id', $line->id)->count());

        $sameUserResponse = $this->actingAs($first)->post(route('businesses.earnings.approve', $line->id));
        $sameUserResponse->assertSessionHasErrors('earning');

        $secondResponse = $this->actingAs($second)->post(route('businesses.earnings.approve', $line->id));
        $secondResponse->assertRedirect(route('businesses.earnings.show', $line->id));
        $secondResponse->assertSessionHas('success', 'Hakediş onaylandı.');

        $line->refresh();
        $this->assertSame('approved', $line->status?->code);
        $this->assertSame($second->id, $line->approved_by);
        $this->assertSame(1, FinanceRevenue::query()->where('earning_line_id', $line->id)->count());
    }

    public function test_manual_create_is_always_fully_approved_even_with_dual_process(): void
    {
        $this->setApprovalProcess('dual');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-08-05',
            'pricing_model' => 'per_package',
            'package_count' => 80,
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
            'status' => 'pending',
        ]);

        $line = EarningLine::query()->first();
        $this->assertNotNull($line);
        $response->assertRedirect(route('businesses.earnings.index', [
            'business_id' => $business->id,
            'period_month' => 8,
            'period_year' => 2026,
        ]));

        $line->load('status');
        $this->assertSame('approved', $line->status?->code);
        $this->assertSame($user->id, $line->approved_by);
        $this->assertSame(1, FinanceRevenue::query()->where('earning_line_id', $line->id)->count());
        $this->assertSame(1, FinancePayment::query()->where('earning_line_id', $line->id)->count());
    }

    public function test_auto_approval_approves_on_create(): void
    {
        $this->setApprovalProcess('auto');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-08-05',
            'pricing_model' => 'per_package',
            'package_count' => 80,
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
            'status' => 'pending',
        ]);

        $line = EarningLine::query()->first();
        $this->assertNotNull($line);
        $response->assertRedirect(route('businesses.earnings.index', [
            'business_id' => $business->id,
            'period_month' => 8,
            'period_year' => 2026,
        ]));

        $line->load('status');
        $this->assertSame('approved', $line->status?->code);
        $this->assertSame($user->id, $line->approved_by);
        $this->assertSame(1, FinanceRevenue::query()->where('earning_line_id', $line->id)->count());
    }

    public function test_approve_all_pending_when_auto_approves_existing_lines(): void
    {
        $this->setApprovalProcess('auto');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $line = $this->createEarning($business, $courier, $user, 'pending_review');

        $result = app(BusinessEarningService::class)->approveAllPendingWhenAuto($user);

        $this->assertSame(1, $result['approved']);
        $line->refresh()->load('status');
        $this->assertSame('approved', $line->status?->code);
        $this->assertSame(1, FinanceRevenue::query()->where('earning_line_id', $line->id)->count());
    }

    public function test_super_admin_can_save_earnings_approval_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->put(route('settings.update', 'earnings'), [
            'default_period' => 'monthly',
            'approval_process' => 'single',
        ]);

        $response->assertRedirect(route('settings.index', ['section' => 'earnings']));
        $response->assertSessionHas('success');

        $this->assertSame('single', app(SettingsManager::class)->group('earnings')->all()['approval_process']);
    }

    private function setApprovalProcess(string $process): void
    {
        app(SettingsManager::class)->group('earnings')->save([
            'default_period' => 'monthly',
            'approval_process' => $process,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBusiness(User $user, array $overrides = []): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Business::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourier(User $user, array $overrides = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }

    private function createEarning(
        Business $business,
        Courier $courier,
        User $user,
        string $statusCode = 'draft',
        array $overrides = [],
    ): EarningLine {
        return EarningLine::factory()->create(array_merge([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'created_by' => $user->id,
            'status_id' => EarningStatus::query()->where('code', $statusCode)->value('id'),
        ], $overrides));
    }
}
