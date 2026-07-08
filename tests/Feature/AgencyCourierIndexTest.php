<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyCourierDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyCourierIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_agency_couriers_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.couriers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_couriers_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.couriers.index'));

        $response->assertOk();
        $response->assertSee('Acenteye Bağlı Kuryeler');
        $response->assertSee('Acentelere bağlı tüm kuryeleri buradan yönetin.');
        $response->assertSee('Kurye Ata');
        $response->assertSee('Toplam Kurye');
        $response->assertSee('Bu Ay Eklenen Kurye');
        $response->assertSee('Emre Demir');
        $response->assertSee('Motosiklet');
    }

    public function test_agency_courier_records_have_at_least_forty_entries(): void
    {
        $records = AgencyCourierDummyData::all();

        $this->assertCount(45, $records);
        $this->assertGreaterThanOrEqual(40, count($records));
    }

    public function test_each_courier_has_at_most_one_current_agency_link(): void
    {
        $currentByCourier = collect(AgencyCourierDummyData::all())
            ->where('is_current', true)
            ->groupBy('courier_id')
            ->map->count();

        foreach ($currentByCourier as $courierId => $count) {
            $this->assertEquals(1, $count, "Courier {$courierId} should have at most one current agency link.");
        }
    }

    public function test_summary_stats_are_calculated(): void
    {
        $summary = AgencyCourierDummyData::summarize();

        $this->assertEquals(45, $summary['total']);
        $this->assertGreaterThan(0, $summary['active']);
        $this->assertGreaterThan(0, $summary['inactive']);
        $this->assertGreaterThan(0, $summary['this_month']);
    }

    public function test_agency_couriers_can_be_filtered_by_agency(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $count = count(AgencyCourierDummyData::filter(['agency_id' => 1]));

        $response = $this->actingAs($user)->get(route('agencies.couriers.index', [
            'agency_id' => 1,
        ]));

        $response->assertOk();
        $response->assertSeeText(number_format($count).' Kayıt');
        $response->assertSee('Emre Demir');
        $response->assertSee('Burak Şen');
    }

    public function test_agency_couriers_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $count = count(AgencyCourierDummyData::filter(['status' => 'on_leave']));

        $response = $this->actingAs($user)->get(route('agencies.couriers.index', [
            'status' => 'on_leave',
        ]));

        $response->assertOk();
        $response->assertSeeText(number_format($count).' Kayıt');
        $response->assertSee('Oğuz Yılmaz');
        $response->assertSee('Caner Bilgin');
    }
}
