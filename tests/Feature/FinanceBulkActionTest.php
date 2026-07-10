<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinancePayment;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceBulkActionTest extends TestCase
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

    public function test_bulk_collect_closes_remaining_amount(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create();
        $pending = FinanceCollection::factory()->for($business)->create([
            'total_amount' => 1000,
            'collected_amount' => 0,
            'status' => 'pending',
        ]);
        $another = FinanceCollection::factory()->for($business)->create([
            'total_amount' => 500,
            'collected_amount' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('finance.collections.bulk'), [
            'ids' => [$pending->id, $another->id],
            'collection_date' => '2026-07-10',
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertRedirect(route('finance.collections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_collections', [
            'id' => $pending->id,
            'status' => 'collected',
            'collected_amount' => 1000,
        ]);
        $this->assertDatabaseHas('finance_collections', [
            'id' => $another->id,
            'status' => 'collected',
            'collected_amount' => 500,
        ]);
    }

    public function test_bulk_pay_closes_remaining_amount(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $pending = FinancePayment::factory()->create([
            'total_amount' => 800,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('finance.payments.bulk'), [
            'ids' => [$pending->id],
            'payment_date' => '2026-07-10',
            'payment_method' => 'eft',
        ]);

        $response->assertRedirect(route('finance.payments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('finance_payments', [
            'id' => $pending->id,
            'status' => 'paid',
            'paid_amount' => 800,
        ]);
    }

    public function test_courier_earning_template_requires_create_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_staff');

        $this->actingAs($user)
            ->get(route('couriers.earnings.template'))
            ->assertForbidden();
    }

    public function test_courier_earning_template_downloads_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('couriers.earnings.template'))
            ->assertOk()
            ->assertDownload();
    }
}
