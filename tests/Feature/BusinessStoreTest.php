<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessStoreTest extends TestCase
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

    public function test_business_store_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('businesses.store'), [
            'company_name' => 'Yeni İşletme Ltd. Şti.',
            'phone' => '0212 111 22 33',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'planned_courier_count' => 4,
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_business_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->post(route('businesses.store'), [
            'company_name' => 'Point Kurye Market Ltd. Şti.',
            'brand_name' => 'Point Market',
            'phone' => '0216 444 55 66',
            'email' => 'info@pointmarket.test',
            'website' => 'https://pointmarket.test',
            'tax_office' => 'Kadıköy',
            'tax_number' => '1234567890',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'address' => 'Test Mahallesi No:1',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'planned_courier_count' => 6,
            'status' => 'active',
            'notes' => 'Canlı kayıt testi',
        ]);

        $business = Business::query()->first();

        $this->assertNotNull($business);
        $response->assertRedirect(route('businesses.show', $business->id));
        $response->assertSessionHas('success', 'İşletme başarıyla oluşturuldu.');

        $this->assertSame('Point Kurye Market Ltd. Şti.', $business->company_name);
        $this->assertSame('Point Market', $business->brand_name);
        $this->assertSame('1234567890', $business->tax_number);
        $this->assertSame(6, (int) $business->planned_courier_count);
        $this->assertSame('weekly', $business->earning_period);
        $this->assertSame('2026-07-14', $business->first_invoice_date?->toDateString());
        $this->assertSame('Canlı kayıt testi', $business->notes);

        $indexResponse = $this->actingAs($user)->get(route('businesses.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Point Market');
        $indexResponse->assertSee('Point Kurye Market Ltd. Şti.');

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Aktif Kontrat');
        $showResponse->assertSee('Aktif kontrat yok');
    }

    public function test_business_store_requires_brand_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->from(route('businesses.create'))->post(route('businesses.store'), [
            'company_name' => 'Markasız İşletme Ltd.',
            'phone' => '0212 111 22 33',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'planned_courier_count' => 3,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.create'));
        $response->assertSessionHasErrors('brand_name');
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_business_store_requires_planned_courier_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->from(route('businesses.create'))->post(route('businesses.store'), [
            'company_name' => 'Eksik Kurye Sayılı Ltd.',
            'brand_name' => 'Eksik Şube',
            'phone' => '0212 111 22 33',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.create'));
        $response->assertSessionHasErrors('planned_courier_count');
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_business_store_requires_first_invoice_date_when_period_selected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->from(route('businesses.create'))->post(route('businesses.store'), [
            'company_name' => 'Faturasız İşletme Ltd.',
            'brand_name' => 'Faturasız',
            'phone' => '0212 111 22 33',
            'earning_period' => 'monthly',
            'planned_courier_count' => 3,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.create'));
        $response->assertSessionHasErrors('first_invoice_date');
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_multiple_businesses_can_share_company_name_and_tax_number(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $payload = [
            'company_name' => 'Ortak Ünvan Gıda A.Ş.',
            'brand_name' => 'Kadıköy Şube',
            'phone' => '0216 111 22 33',
            'tax_number' => '9988776655',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-08-01',
            'planned_courier_count' => 3,
            'status' => 'active',
        ];

        $first = $this->actingAs($user)->post(route('businesses.store'), $payload);
        $firstBusiness = Business::query()->latest('id')->first();
        $this->assertNotNull($firstBusiness);
        $first->assertRedirect(route('businesses.show', $firstBusiness->id));

        $second = $this->actingAs($user)->post(route('businesses.store'), array_merge($payload, [
            'brand_name' => 'Beşiktaş Şube',
            'phone' => '0212 222 33 44',
            'district' => 'Beşiktaş',
        ]));

        $secondBusiness = Business::query()->latest('id')->first();
        $this->assertNotNull($secondBusiness);
        $second->assertRedirect(route('businesses.show', $secondBusiness->id));
        $second->assertSessionDoesntHaveErrors();

        $this->assertSame(2, Business::query()->where('company_name', 'Ortak Ünvan Gıda A.Ş.')->count());
        $this->assertSame(2, Business::query()->where('tax_number', '9988776655')->count());
        $this->assertNotSame($firstBusiness->id, $secondBusiness->id);
    }

    public function test_super_admin_can_delete_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->post(route('businesses.store'), [
            'company_name' => 'Silinecek İşletme Ltd.',
            'brand_name' => 'Silinecek',
            'phone' => '0212 111 22 33',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-08-01',
            'planned_courier_count' => 3,
            'status' => 'active',
        ])->assertRedirect();

        $business = Business::query()->where('brand_name', 'Silinecek')->firstOrFail();

        $this->actingAs($user)
            ->delete(route('businesses.destroy', $business->id))
            ->assertRedirect(route('businesses.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('businesses', ['id' => $business->id]);
    }

    public function test_non_super_admin_cannot_delete_business(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->post(route('businesses.store'), [
            'company_name' => 'Korunan İşletme Ltd.',
            'brand_name' => 'Korunan',
            'phone' => '0212 333 44 55',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-08-01',
            'planned_courier_count' => 2,
            'status' => 'active',
        ])->assertRedirect();

        $business = Business::query()->where('brand_name', 'Korunan')->firstOrFail();

        $manager = User::factory()->create();
        $manager->assignRole('general_manager');

        $this->actingAs($manager)
            ->delete(route('businesses.destroy', $business->id))
            ->assertForbidden();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'deleted_at' => null]);
    }
}
