<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Notification\Notifications\SystemNotification;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ContractExpiryNotificationTest extends TestCase
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

    public function test_super_admin_can_open_contract_expiry_notification(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = $this->createBusiness($user);
        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        Artisan::call('crmlog:reminders:contracts');

        $notification = $user->fresh()->notifications()
            ->where('data->type', 'contract_expiry')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('businesses.contracts.show', $contract->id));

        $this->actingAs($user)
            ->get(route('businesses.contracts.show', $contract->id))
            ->assertOk()
            ->assertSee('Sözleşme Bilgileri');
    }

    public function test_operations_specialist_is_redirected_to_business_card_from_contract_notification(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $business = $this->createBusiness($user);
        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $user->notify(new SystemNotification(
            type: 'contract_expiry',
            title: 'Sözleşme Bitiş Hatırlatması',
            message: 'Test',
            actionUrl: route('businesses.contracts.show', $contract->id, absolute: false),
            meta: [
                'contract_id' => $contract->id,
                'business_id' => $business->id,
            ],
        ));

        $notification = $user->fresh()->notifications()->latest()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('businesses.show', $business->id));

        $this->actingAs($user)
            ->get(route('businesses.show', $business->id))
            ->assertOk();
    }

    public function test_deleted_contract_notification_falls_back_to_contract_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = $this->createBusiness($user);
        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'created_by' => $user->id,
        ]);

        $contract->delete();

        $user->notify(new SystemNotification(
            type: 'contract_expiry',
            title: 'Sözleşme Bitiş Hatırlatması',
            message: 'Test',
            actionUrl: route('businesses.contracts.show', $contract->id, absolute: false),
            meta: [
                'contract_id' => $contract->id,
                'business_id' => $business->id,
            ],
        ));

        $notification = $user->fresh()->notifications()->latest()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('businesses.contracts.index', ['business_id' => $business->id]));
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
}
