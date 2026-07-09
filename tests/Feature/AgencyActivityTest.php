<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Services\AgencyActivityService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyActivityTest extends TestCase
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

    public function test_agency_activities_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.activities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_activities_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user, ['company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.']);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'agency_created',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'description' => 'Hızlı Kurye Acentesi Ltd. Şti. acentesi sisteme kaydedildi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'contract_renewed',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'description' => 'Sözleşme yenilendi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'earning_created',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'description' => 'Hakediş kaydı oluşturuldu.',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.activities.index'));

        $response->assertOk();
        $response->assertSee('Hareket Geçmişi');
        $response->assertSee('Acenteler üzerinde gerçekleştirilen tüm işlemleri görüntüleyin.');
        $response->assertSee('Toplam Hareket');
        $response->assertSee('Bugünkü Hareket');
        $response->assertSee('Bu Hafta');
        $response->assertSee('Bu Ay');
        $response->assertSee('Acente Oluşturuldu');
        $response->assertSee('Sözleşme Yenilendi');
        $response->assertSee('Hakediş Oluşturuldu');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
    }

    public function test_summary_stats_are_calculated(): void
    {
        $user = User::factory()->create();
        $agency = $this->createAgency($user);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'agency_created',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'created_at' => now(),
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'agency_updated',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'created_at' => now()->subDays(10),
        ]);

        $summary = app(AgencyActivityService::class)->summary();

        $this->assertEquals(2, $summary['count']);
        $this->assertGreaterThanOrEqual(1, $summary['today']);
        $this->assertGreaterThanOrEqual($summary['today'], $summary['this_week']);
        $this->assertGreaterThanOrEqual($summary['this_week'], $summary['this_month']);
    }

    public function test_agency_activities_can_be_filtered_by_action(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user, ['company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.']);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'agency_created',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'description' => 'Hızlı Kurye Acentesi Ltd. Şti. acentesi sisteme kaydedildi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'agency_updated',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'description' => 'Acente bilgileri güncellendi.',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.activities.index', [
            'agency_id' => $agency->id,
            'action' => 'agency_created',
        ]));

        $response->assertOk();
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti. acentesi sisteme kaydedildi.');
        $response->assertDontSee('Acente bilgileri güncellendi.');
    }

    public function test_agency_activities_can_be_filtered_by_user(): void
    {
        $actor = User::factory()->create(['name' => 'Elif Demir']);
        $actor->assignRole('super_admin');
        $otherUser = User::factory()->create(['name' => 'Mehmet Kaya']);
        $agency = $this->createAgency($actor);

        ActivityLog::factory()->create([
            'user_id' => $actor->id,
            'action' => 'agency_updated',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'description' => 'Elif tarafından güncellendi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $otherUser->id,
            'action' => 'agency_updated',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'description' => 'Mehmet tarafından güncellendi.',
        ]);

        $response = $this->actingAs($actor)->get(route('agencies.activities.index', [
            'user_id' => $actor->id,
        ]));

        $response->assertOk();
        $response->assertSee('Elif Demir');
        $response->assertSee('Elif tarafından güncellendi.');
        $response->assertDontSee('Mehmet tarafından güncellendi.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAgency(User $user, array $overrides = []): Agency
    {
        return Agency::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
