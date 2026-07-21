<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Services\CourierActivityService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierActivityTest extends TestCase
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

    public function test_activities_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.activities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_activities_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_created',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'description' => 'Ahmet Yıldız kuryesi sisteme kaydedildi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'earning_created',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'description' => 'Ahmet Yıldız için hakediş kaydı oluşturuldu.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'bank_account_added',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'description' => 'Ahmet Yıldız için banka hesabı eklendi.',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.activities.index'));

        $response->assertOk();
        $response->assertSee('Hareket Geçmişi');
        $response->assertSee('Kuryeler üzerinde gerçekleştirilen tüm işlemleri görüntüleyin.');
        $response->assertSee('Toplam Hareket');
        $response->assertSee('Bugünkü Hareketler');
        $response->assertSee('Bu Hafta');
        $response->assertSee('Bu Ay');
        $response->assertSee('Kurye Oluşturuldu');
        $response->assertSee('Hakediş Oluşturuldu');
        $response->assertSee('Banka Hesabı Eklendi');
    }

    public function test_summary_stats_are_calculated(): void
    {
        $user = User::factory()->create();
        $courier = $this->createCourier($user);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_created',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'created_at' => now(),
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_updated',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'created_at' => now()->subDays(10),
        ]);

        $summary = app(CourierActivityService::class)->summary();

        $this->assertEquals(2, $summary['count']);
        $this->assertGreaterThanOrEqual(1, $summary['today']);
        $this->assertGreaterThanOrEqual($summary['today'], $summary['this_week']);
        $this->assertGreaterThanOrEqual($summary['this_week'], $summary['this_month']);
    }

    public function test_activities_can_be_filtered_by_action(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $firstCourier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);
        $secondCourier = $this->createCourier($user, ['full_name' => 'Murat Kaya']);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_created',
            'subject_type' => Courier::class,
            'subject_id' => $firstCourier->id,
            'description' => 'Ahmet Yıldız kuryesi sisteme kaydedildi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_created',
            'subject_type' => Courier::class,
            'subject_id' => $secondCourier->id,
            'description' => 'Murat Kaya kuryesi sisteme kaydedildi.',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.activities.index', [
            'courier_id' => $firstCourier->id,
            'action' => 'courier_created',
        ]));

        $response->assertOk();
        $response->assertSee('Ahmet Yıldız kuryesi sisteme kaydedildi.');
        $response->assertDontSee('Murat Kaya kuryesi sisteme kaydedildi.');
    }

    public function test_activities_can_be_filtered_by_user(): void
    {
        $actor = User::factory()->create(['name' => 'Elif Demir']);
        $actor->assignRole('super_admin');
        $otherUser = User::factory()->create(['name' => 'Mehmet Kaya']);
        $courier = $this->createCourier($actor);

        ActivityLog::factory()->create([
            'user_id' => $actor->id,
            'action' => 'courier_updated',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'description' => 'Profil güncellendi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $otherUser->id,
            'action' => 'courier_updated',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'description' => 'Başka kullanıcı güncelledi.',
        ]);

        $response = $this->actingAs($actor)->get(route('couriers.activities.index', [
            'user_id' => $actor->id,
        ]));

        $response->assertOk();
        $response->assertSee('Elif Demir');
        $response->assertDontSee('Başka kullanıcı güncelledi.');
    }

    public function test_activities_index_handles_nested_change_values(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, ['full_name' => 'İç İçe JSON Kurye']);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_updated',
            'subject_type' => Courier::class,
            'subject_id' => $courier->id,
            'description' => 'İç içe değerlerle güncellendi.',
            'old_values' => ['profile' => ['city' => 'İstanbul']],
            'new_values' => [
                'profile' => ['city' => 'Ankara'],
                'tags' => ['a', 'b'],
                'active' => true,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('couriers.activities.index'))
            ->assertOk()
            ->assertSee('İç İçe JSON Kurye')
            ->assertSee('İç içe değerlerle güncellendi.');
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
