<?php

namespace Tests\Feature;

use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Support\DemoDataGuard;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_guard_allows_local_and_testing(): void
    {
        $this->assertTrue(DemoDataGuard::isAllowed());
        DemoDataGuard::assertAllowed();
    }

    public function test_demo_data_guard_blocks_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $this->assertFalse(DemoDataGuard::isAllowed());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('local/testing');

        DemoDataGuard::assertAllowed();
    }

    public function test_seed_demo_command_fails_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $this->artisan('crmlog:seed-demo', ['--force' => true])
            ->assertFailed();
    }

    public function test_clear_demo_command_fails_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $this->artisan('crmlog:clear-demo', ['--force' => true])
            ->assertFailed();
    }

    public function test_demo_data_seeder_creates_sample_records(): void
    {
        $this->seed([
            \Database\Seeders\LookupTableSeeder::class,
            \Database\Seeders\CitySeeder::class,
            \Database\Seeders\RoleAndPermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);

        $this->assertDatabaseHas('businesses', [
            'tax_number' => '9000001001',
            'brand_name' => 'Ateş & Odun',
            'status' => 'active',
            'notes' => DemoDataSeeder::MARKER,
        ]);
        $this->assertDatabaseHas('businesses', [
            'brand_name' => 'Şekerci Han',
            'status' => 'opening_stage',
            'notes' => DemoDataSeeder::MARKER,
        ]);
        $this->assertDatabaseHas('businesses', [
            'brand_name' => 'Mantı Evi',
            'status' => 'contract_stage',
        ]);
        $this->assertDatabaseHas('businesses', [
            'brand_name' => 'Börekçi Usta',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('businesses', [
            'brand_name' => 'Meze Bar',
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('agencies', [
            'tax_number' => '9000000001',
            'brand_name' => 'Hızlı Rota',
            'notes' => DemoDataSeeder::MARKER,
        ]);
        $this->assertDatabaseHas('couriers', [
            'tc_number' => '90000000001',
            'first_name' => 'Ahmet',
            'last_name' => 'Yılmaz',
            'notes' => DemoDataSeeder::MARKER,
        ]);
        $this->assertDatabaseHas('earning_lines', ['description' => DemoDataSeeder::MARKER]);
        $this->assertDatabaseHas('finance_revenues', ['description' => DemoDataSeeder::MARKER]);
        $this->assertSame(4, Business::query()->where('notes', DemoDataSeeder::MARKER)->where('status', 'opening_stage')->count());
    }

    public function test_clear_demo_removes_seeded_records(): void
    {
        $this->seed([
            \Database\Seeders\LookupTableSeeder::class,
            \Database\Seeders\CitySeeder::class,
            \Database\Seeders\RoleAndPermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);

        $this->artisan('crmlog:clear-demo', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame(0, Business::withTrashed()->where('notes', DemoDataSeeder::MARKER)->count());
        $this->assertSame(0, Agency::withTrashed()->where('notes', DemoDataSeeder::MARKER)->count());
        $this->assertSame(0, Courier::withTrashed()->where('notes', DemoDataSeeder::MARKER)->count());
        $this->assertDatabaseMissing('earning_lines', ['description' => DemoDataSeeder::MARKER]);
        $this->assertDatabaseMissing('finance_revenues', ['description' => DemoDataSeeder::MARKER]);
        $this->assertDatabaseMissing('business_shifts', ['notes' => DemoDataSeeder::MARKER]);
        $this->assertDatabaseMissing('stock_products', ['notes' => DemoDataSeeder::MARKER]);
        $this->assertDatabaseHas('users', ['email' => 'admin@crmlog.com']);
        $this->assertSame(1, \App\Models\User::query()->count());
    }

    public function test_demo_data_includes_shifts_and_stock(): void
    {
        $this->seed([
            \Database\Seeders\LookupTableSeeder::class,
            \Database\Seeders\CitySeeder::class,
            \Database\Seeders\RoleAndPermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);

        $this->assertGreaterThan(0, \App\Modules\ShiftPlanning\Models\BusinessShift::query()->where('notes', DemoDataSeeder::MARKER)->count());
        $this->assertGreaterThan(0, \App\Modules\Stock\Models\StockProduct::query()->where('notes', DemoDataSeeder::MARKER)->count());
        $this->assertGreaterThan(0, \App\Modules\Stock\Models\StockAssignment::query()->where('notes', DemoDataSeeder::MARKER)->count());
    }

    public function test_wipe_data_keeps_only_super_admin(): void
    {
        $this->seed([
            \Database\Seeders\LookupTableSeeder::class,
            \Database\Seeders\CitySeeder::class,
            \Database\Seeders\RoleAndPermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);

        $this->artisan('crmlog:wipe-data', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame(0, Business::withTrashed()->count());
        $this->assertSame(0, Agency::withTrashed()->count());
        $this->assertSame(0, Courier::withTrashed()->count());
        $this->assertDatabaseHas('users', ['email' => 'admin@crmlog.com']);
        $this->assertDatabaseMissing('users', ['email' => 'mudur@crmlog.com']);
        $this->assertDatabaseMissing('users', ['email' => 'operasyon@crmlog.com']);
        $this->assertSame(1, \App\Models\User::query()->count());
    }

    public function test_demo_data_covers_july_month_and_live_board_states(): void
    {
        $this->seed([
            \Database\Seeders\LookupTableSeeder::class,
            \Database\Seeders\CitySeeder::class,
            \Database\Seeders\RoleAndPermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);

        $monthStart = now()->copy()->startOfMonth()->toDateString();
        $monthEnd = now()->copy()->endOfMonth()->toDateString();

        $shift = \App\Modules\ShiftPlanning\Models\BusinessShift::query()
            ->where('notes', DemoDataSeeder::MARKER)
            ->where('name', 'Öğle Vardiyası')
            ->first();

        $this->assertNotNull($shift);
        $this->assertSame($monthStart, $shift->start_date?->toDateString());
        $this->assertSame($monthEnd, $shift->end_date?->toDateString());

        $this->assertGreaterThan(
            0,
            \App\Modules\ShiftPlanning\Models\BusinessShiftAttendance::query()
                ->where('notes', DemoDataSeeder::MARKER)
                ->whereDate('work_date', '>=', $monthStart)
                ->whereDate('work_date', '<', now()->toDateString())
                ->where('status', 'completed')
                ->count()
        );

        $this->assertGreaterThan(
            0,
            \App\Modules\ShiftPlanning\Models\BusinessShiftAttendance::query()
                ->where('notes', DemoDataSeeder::MARKER)
                ->whereDate('work_date', now()->toDateString())
                ->where('status', 'in_progress')
                ->count()
        );

        $this->assertDatabaseHas('business_shifts', [
            'name' => 'Eksik Kadro Operasyon',
            'notes' => DemoDataSeeder::MARKER,
        ]);
        $this->assertGreaterThan(
            0,
            \App\Modules\ShiftPlanning\Models\BusinessShift::query()
                ->where('notes', DemoDataSeeder::MARKER)
                ->where('name', 'Yaklaşan Operasyon')
                ->count()
        );
    }
}
