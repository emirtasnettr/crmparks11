<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Data\CourierWorkHistoryDummyData;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierWorkHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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

        $response = $this->actingAs($user)->get(route('couriers.work-history.show', 3));

        $response->assertOk();
        $response->assertSee('Çalışma Detayı');
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('İşletme Bilgileri');
        $response->assertSee('Burger House\'a geri dönüş.');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_work_duration_is_calculated_from_dates(): void
    {
        $record = CourierWorkHistoryDummyData::find(1);

        $this->assertNotNull($record);
        $this->assertNotEmpty($record['work_duration']);
        $this->assertGreaterThan(0, $record['work_duration_days']);

        $start = Carbon::parse($record['start_date']);
        $end = Carbon::parse($record['end_date']);
        $expectedDays = $start->diffInDays($end) + 1;

        $this->assertEquals($expectedDays, $record['work_duration_days']);
    }

    public function test_all_work_history_records_are_preserved(): void
    {
        $records = CourierWorkHistoryDummyData::all();

        $this->assertCount(52, $records);
        $this->assertGreaterThanOrEqual(50, count($records));
    }

    public function test_work_history_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

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

        $response = $this->actingAs($user)->get(route('couriers.work-history.index', [
            'business_id' => 4,
        ]));

        $response->assertOk();
        $response->assertSee('01.03.2026');
        $response->assertDontSee('01.01.2025');
    }
}
