<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
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
            'period_month' => 7,
            'period_year' => 2026,
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
            'description' => 'Güncellendi',
        ]);

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
            'period_month' => 7,
            'period_year' => 2026,
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
            'period_month' => 8,
            'period_year' => 2026,
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
