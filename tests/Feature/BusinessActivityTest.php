<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Services\BusinessActivityService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessActivityTest extends TestCase
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
        $response = $this->get(route('businesses.activities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_activities_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, ['company_name' => 'Burger House Gıda Ltd. Şti.']);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'business_created',
            'subject_type' => Business::class,
            'subject_id' => $business->id,
            'description' => 'Burger House işletmesi sisteme kaydedildi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_assigned',
            'subject_type' => Business::class,
            'subject_id' => $business->id,
            'description' => 'Ali Demir kuryesi operasyona atandı.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'document_uploaded',
            'subject_type' => Business::class,
            'subject_id' => $business->id,
            'description' => 'Şube listesi Excel dosyası yüklendi.',
        ]);

        $response = $this->actingAs($user)->get(route('businesses.activities.index'));

        $response->assertOk();
        $response->assertSee('Hareket Geçmişi');
        $response->assertSee('İşletmeler üzerinde yapılan tüm işlemleri görüntüleyin.');
        $response->assertSee('Kurye Atandı');
        $response->assertSee('Evrak Yüklendi');
        $response->assertSee('Hakediş Oluşturuldu', false);
        $response->assertSee('İşletme Oluşturuldu');
    }

    public function test_activities_can_be_filtered_by_action(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, ['company_name' => 'Burger House Gıda Ltd. Şti.']);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'business_created',
            'subject_type' => Business::class,
            'subject_id' => $business->id,
            'description' => 'Burger House işletmesi sisteme kaydedildi.',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'courier_assigned',
            'subject_type' => Business::class,
            'subject_id' => $business->id,
            'description' => 'Ali Demir kuryesi operasyona atandı.',
        ]);

        $response = $this->actingAs($user)->get(route('businesses.activities.index', [
            'action' => 'business_created',
        ]));

        $response->assertOk();
        $response->assertSee('Burger House işletmesi sisteme kaydedildi.');
        $response->assertDontSee('Ali Demir kuryesi operasyona atandı.');
    }

    public function test_activities_are_loaded_from_database(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);

        ActivityLog::factory()->count(3)->create([
            'user_id' => $user->id,
            'action' => 'business_updated',
            'subject_type' => Business::class,
            'subject_id' => $business->id,
        ]);

        $rows = app(BusinessActivityService::class)->filter([]);

        $this->assertGreaterThanOrEqual(3, $rows->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBusiness(User $user, array $overrides = []): Business
    {
        return Business::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
