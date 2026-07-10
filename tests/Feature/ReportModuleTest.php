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
        $user->assignRole('operations_staff');

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
            ->assertSee('Operasyon Özeti');

        $this->actingAs($user)->get(route('reports.earnings'))
            ->assertOk()
            ->assertSee('Hakediş Özeti')
            ->assertSee($business->company_name);

        $this->actingAs($user)->get(route('reports.operations'))
            ->assertOk()
            ->assertSee('Operasyon Özeti');
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
            ->assertSee($business->company_name);

        $this->actingAs($user)->get(route('reports.collections.export'))
            ->assertOk();
    }

    public function test_user_without_financial_permission_cannot_open_collections_report(): void
    {
        $user = User::factory()->create();
        $user->assignRole('regional_coordinator');

        $this->actingAs($user)->get(route('reports.index'))->assertOk();
        $this->actingAs($user)->get(route('reports.collections'))->assertForbidden();
    }
}
