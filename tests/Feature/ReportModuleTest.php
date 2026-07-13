<?php

namespace Tests\Feature;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceCollection;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportModuleTest extends TestCase
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

    public function test_reports_index_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    }

    public function test_super_admin_can_view_reports_index_and_earnings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create(['created_by' => $user->id]);
        $courier = Courier::factory()->create(['created_by' => $user->id]);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Raporlar')
            ->assertSee('Hakediş Özeti')
            ->assertSee('Tahsilat Yaşlandırma')
            ->assertSee('Operasyon Özeti')
            ->assertSee('Kurye Performansı')
            ->assertSee('Acente Payı');

        $this->actingAs($user)->get(route('reports.earnings'))
            ->assertOk()
            ->assertSee('Hakediş Özeti')
            ->assertSee($business->displayName());

        $this->actingAs($user)->get(route('reports.operations'))
            ->assertOk()
            ->assertSee('Operasyon Özeti');

        $this->actingAs($user)->get(route('reports.courier-performance'))
            ->assertOk()
            ->assertSee('Kurye Performansı')
            ->assertSee($courier->full_name);
    }

    public function test_collections_report_and_export(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create(['created_by' => $user->id]);

        FinanceCollection::factory()->create([
            'business_id' => $business->id,
            'status' => 'pending',
            'due_date' => now()->subDays(10)->toDateString(),
            'total_amount' => 8000,
            'collected_amount' => 0,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('reports.collections'))
            ->assertOk()
            ->assertSee('Tahsilat Yaşlandırma')
            ->assertSee($business->displayName());

        $this->actingAs($user)->get(route('reports.collections.export'))
            ->assertOk();
    }

    public function test_agency_share_report(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = \App\Modules\Agency\Models\Agency::factory()->create(['created_by' => $user->id]);
        $business = Business::factory()->create(['created_by' => $user->id]);
        $courier = Courier::factory()->create([
            'created_by' => $user->id,
            'agency_id' => $agency->id,
        ]);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'agency_payment' => 1500,
            'status_id' => EarningStatus::query()->where('code', 'approved')->value('id'),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('reports.agency-share'))
            ->assertOk()
            ->assertSee('Acente Payı')
            ->assertSee($agency->displayName());

        $this->actingAs($user)->get(route('reports.agency-share.export'))
            ->assertOk();
    }

    public function test_user_without_financial_permission_cannot_open_collections_report(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales_manager');

        $this->actingAs($user)->get(route('reports.index'))->assertOk();
        $this->actingAs($user)->get(route('reports.collections'))->assertForbidden();
    }
}
