<?php

namespace Tests\Feature;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinancialAdjustment;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
            CitySeeder::class,
        ]);
    }

    public function test_super_admin_can_credit_courier_earning_with_reason(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $line = EarningLine::factory()->create([
            'created_by' => $admin->id,
            'extra_payment' => 0,
            'deduction' => 0,
        ]);
        $line->refresh();
        $beforeNet = (float) $line->net_courier_payment;

        $this->actingAs($admin)
            ->post(route('couriers.financial-adjustments.store', $line->courier_id), [
                'direction' => 'credit',
                'amount' => 150.5,
                'reason' => 'Geçmiş dönem eksik ödeme düzeltmesi',
                'earning_line_id' => $line->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $adjustment = FinancialAdjustment::query()->first();
        $this->assertNotNull($adjustment);
        $this->assertSame('courier', $adjustment->target_type);
        $this->assertSame((int) $line->courier_id, (int) $adjustment->target_id);
        $this->assertSame('credit', $adjustment->direction);
        $this->assertEquals(150.5, (float) $adjustment->amount);
        $this->assertSame('Geçmiş dönem eksik ödeme düzeltmesi', $adjustment->reason);
        $this->assertNotNull($adjustment->current_account_movement_id);

        $movement = CurrentAccountMovement::query()->find($adjustment->current_account_movement_id);
        $this->assertNotNull($movement);
        $this->assertSame('credit_note', $movement->type);
        $this->assertEquals(150.5, (float) $movement->credit);
        $this->assertSame('financial_adjustment', $movement->related_type);

        $line->refresh();
        $this->assertEquals(150.5, (float) $line->extra_payment);
        $this->assertEquals(round($beforeNet + 150.5, 2), (float) $line->net_courier_payment);

        $this->assertTrue(
            ActivityLog::query()->where('action', 'financial_adjustment_created')->exists()
        );
    }

    public function test_super_admin_can_debit_business_earning_with_reason(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $line = EarningLine::factory()->create([
            'created_by' => $admin->id,
            'extra_payment' => 0,
            'deduction' => 10,
        ]);
        $beforeDeduction = (float) $line->deduction;

        $this->actingAs($admin)
            ->post(route('businesses.financial-adjustments.store', $line->business_id), [
                'direction' => 'debit',
                'amount' => 40,
                'reason' => 'Fazla fatura mahsubu yapıldı',
                'earning_line_id' => $line->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $adjustment = FinancialAdjustment::query()->first();
        $this->assertNotNull($adjustment);
        $this->assertSame('business', $adjustment->target_type);
        $this->assertSame('debit', $adjustment->direction);

        $movement = CurrentAccountMovement::query()->find($adjustment->current_account_movement_id);
        $this->assertNotNull($movement);
        $this->assertSame('debit_note', $movement->type);
        $this->assertEquals(40.0, (float) $movement->debit);

        $line->refresh();
        $this->assertEquals($beforeDeduction + 40, (float) $line->deduction);
    }

    public function test_reason_is_required(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = Courier::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->from(route('couriers.show', $courier->id))
            ->post(route('couriers.financial-adjustments.store', $courier->id), [
                'direction' => 'credit',
                'amount' => 10,
                'reason' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('reason');

        $this->assertSame(0, FinancialAdjustment::query()->count());
    }

    public function test_non_super_admin_cannot_adjust(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');
        $business = Business::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('businesses.financial-adjustments.store', $business->id), [
                'direction' => 'credit',
                'amount' => 25,
                'reason' => 'Yetkisiz deneme kaydı',
            ])
            ->assertForbidden();

        $this->assertSame(0, FinancialAdjustment::query()->count());
    }
}
