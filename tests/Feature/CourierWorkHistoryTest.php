<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Carbon\Carbon;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierWorkHistoryTest extends TestCase
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

    public function test_work_history_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.work-history.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_work_history_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, [
            'full_name' => 'Ahmet Yıldız',
        ]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.work-history.index'));

        $response->assertOk();
        $response->assertSee('Çalışma Geçmişi');
        $response->assertSee('Kuryelerin geçmiş ve aktif çalışma kayıtlarını görüntüleyin.');
        $response->assertSee('Toplam Çalışma Kaydı');
        $response->assertSee('Aktif Görev');
        $response->assertSee('Tamamlanan Görev');
        $response->assertSee('Bu Ay Başlayan Görev');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_authenticated_user_can_view_work_history_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, [
            'company_name' => 'Burger House',
        ]);
        $courier = $this->createCourier($user, [
            'full_name' => 'Ahmet Yıldız',
        ]);

        $assignment = BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
            'notes' => 'Burger House\'a geri dönüş.',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.work-history.show', $assignment->id));

        $response->assertOk();
        $response->assertSee('Çalışma Detayı');
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Burger House\'a geri dönüş.');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_work_duration_is_calculated_from_dates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $assignment = BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-03-31',
            'status' => 'completed',
        ]);

        $record = app(\App\Modules\Courier\Services\CourierWorkHistoryPresenter::class)->showRow($assignment);

        $this->assertNotEmpty($record['work_duration']);
        $this->assertGreaterThan(0, $record['work_duration_days']);

        $start = Carbon::parse($record['start_date']);
        $end = Carbon::parse($record['end_date']);
        $expectedDays = $start->diffInDays($end) + 1;

        $this->assertEquals($expectedDays, $record['work_duration_days']);
    }

    public function test_work_history_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-30',
            'status' => 'completed',
        ]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.work-history.index', [
            'status' => 'completed',
        ]));

        $response->assertOk();
        $response->assertSee('01.01.2025');
        $response->assertDontSee('01.01.2026');
    }

    public function test_work_history_can_be_filtered_by_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $firstBusiness = $this->createBusiness($user, ['company_name' => 'İlk İşletme']);
        $secondBusiness = $this->createBusiness($user, ['company_name' => 'İkinci İşletme']);
        $courier = $this->createCourier($user);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $firstBusiness->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'completed',
        ]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $secondBusiness->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2026-03-01',
            'end_date' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.work-history.index', [
            'business_id' => $secondBusiness->id,
        ]));

        $response->assertOk();
        $response->assertSee('01.03.2026');
        $response->assertDontSee('01.01.2025');
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
}
