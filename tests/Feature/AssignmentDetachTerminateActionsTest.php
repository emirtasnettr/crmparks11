<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Services\CashFlowService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentDetachTerminateActionsTest extends TestCase
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

    public function test_agency_courier_can_be_detached(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $agency = Agency::factory()->create(['created_by' => $user->id]);
        $courier = Courier::factory()->create([
            'agency_id' => $agency->id,
            'courier_type' => 'agency',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('agencies.couriers.detach', $courier->id))
            ->assertRedirect(route('agencies.couriers.index', ['agency_id' => $agency->id]));

        $courier->refresh();
        $this->assertNull($courier->agency_id);
        $this->assertSame('independent', $courier->courier_type);
    }

    public function test_business_assignment_can_be_terminated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['created_by' => $user->id]);
        $courier = Courier::factory()->create(['created_by' => $user->id]);
        $assignment = BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => null,
            'status' => 'active',
            'assigned_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('businesses.assignments.terminate', $assignment->id))
            ->assertRedirect(route('businesses.assignments.index', ['business_id' => $business->id]));

        $assignment->refresh();
        $this->assertSame('inactive', $assignment->status);
        $this->assertNotNull($assignment->end_date);
    }

    public function test_cash_flow_rows_include_related_document_urls(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $collection = FinanceCollection::factory()->collected()->create([
            'created_by' => $user->id,
        ]);

        $analysis = app(CashFlowService::class)->analyze([
            'period' => 'year',
            'page' => 1,
        ]);

        $match = collect($analysis['transactions'] ?? [])->first(
            fn (array $row) => ($row['source_module'] ?? null) === 'collections'
                && (int) ($row['source_id'] ?? 0) === $collection->id
        );

        $this->assertNotNull($match);
        $this->assertSame(
            route('finance.collections.show', $collection->id),
            $match['related_url'],
        );
    }
}
