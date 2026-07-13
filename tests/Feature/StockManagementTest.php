<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use App\Modules\Stock\Models\StockAssignment;
use App\Modules\Stock\Models\StockProduct;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
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

    public function test_stock_index_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('stock.products.index'))->assertForbidden();
    }

    public function test_admin_can_create_product_and_assign_to_courier(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        $this->actingAs($user)
            ->post(route('stock.products.store'), [
                'name' => 'Kurye Kaskı',
                'sku' => 'KSK-001',
                'description' => 'Standart kask',
                'quantity' => 10,
                'unit' => 'adet',
                'status' => 'active',
                'notes' => null,
            ])
            ->assertRedirect();

        $product = StockProduct::query()->first();
        $this->assertNotNull($product);
        $this->assertSame(10, $product->quantity);

        $this->actingAs($user)
            ->post(route('stock.products.assign', $product->id), [
                'courier_id' => $courier->id,
                'quantity' => 2,
                'assigned_at' => now()->toDateString(),
                'notes' => 'Teslim edildi',
            ])
            ->assertRedirect(route('stock.products.show', $product->id));

        $product->refresh();
        $this->assertSame(8, $product->quantity);
        $this->assertDatabaseHas('stock_assignments', [
            'stock_product_id' => $product->id,
            'courier_id' => $courier->id,
            'quantity' => 2,
            'status' => 'assigned',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'stock_product_created',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'stock_assigned',
            'user_id' => $user->id,
        ]);
    }

    public function test_quantity_update_and_return_are_logged_in_history(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        $product = StockProduct::query()->create([
            'name' => 'Termal Çanta',
            'quantity' => 5,
            'unit' => 'adet',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('stock.products.update', $product->id), [
                'name' => 'Termal Çanta',
                'sku' => null,
                'description' => null,
                'quantity' => 8,
                'unit' => 'adet',
                'status' => 'active',
                'notes' => null,
            ])
            ->assertRedirect(route('stock.products.show', $product->id));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'stock_quantity_increased',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('stock.products.update', $product->id), [
                'name' => 'Termal Çanta',
                'sku' => null,
                'description' => null,
                'quantity' => 3,
                'unit' => 'adet',
                'status' => 'active',
                'notes' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'stock_quantity_decreased',
            'user_id' => $user->id,
        ]);

        $assignment = StockAssignment::query()->create([
            'stock_product_id' => $product->id,
            'courier_id' => $courier->id,
            'quantity' => 1,
            'assigned_at' => now()->toDateString(),
            'status' => 'assigned',
            'assigned_by' => $user->id,
        ]);
        $product->update(['quantity' => 2]);

        $this->actingAs($user)
            ->post(route('stock.assignments.return', $assignment->id))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'stock_returned',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('stock.activity.index'))
            ->assertOk()
            ->assertSee('Kayıt Geçmişi')
            ->assertSee('Stok Artırıldı')
            ->assertSee('Stok Düşürüldü')
            ->assertSee('Zimmet İade Alındı');
    }

    public function test_failed_assign_does_not_create_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);
        $product = StockProduct::query()->create([
            'name' => 'Yelek',
            'quantity' => 1,
            'unit' => 'adet',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $before = \App\Modules\ActivityLog\Models\ActivityLog::query()->count();

        $this->actingAs($user)
            ->from(route('stock.products.show', $product->id))
            ->post(route('stock.products.assign', $product->id), [
                'courier_id' => $courier->id,
                'quantity' => 5,
                'assigned_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('stock.products.show', $product->id))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(1, $product->fresh()->quantity);
        $this->assertDatabaseCount('stock_assignments', 0);
        $this->assertSame($before, \App\Modules\ActivityLog\Models\ActivityLog::query()->count());
    }

    public function test_returning_assignment_restores_stock(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');
        $courier = $this->createCourier($user);
        $product = StockProduct::query()->create([
            'name' => 'Termal Çanta',
            'quantity' => 3,
            'unit' => 'adet',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $assignment = StockAssignment::query()->create([
            'stock_product_id' => $product->id,
            'courier_id' => $courier->id,
            'quantity' => 2,
            'assigned_at' => now()->toDateString(),
            'status' => 'assigned',
            'assigned_by' => $user->id,
        ]);
        $product->update(['quantity' => 1]);

        $this->actingAs($user)
            ->post(route('stock.assignments.return', $assignment->id))
            ->assertRedirect();

        $this->assertSame(3, $product->fresh()->quantity);
        $this->assertSame('returned', $assignment->fresh()->status);
    }

    public function test_operations_specialist_sees_stock_menu(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stok Yönetimi')
            ->assertSee(route('stock.products.index'), false)
            ->assertSee(route('stock.activity.index'), false);
    }

    public function test_inventory_dashboard_shows_products_critical_stock_and_recent_assignments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');
        $courier = $this->createCourier($user);

        $critical = StockProduct::query()->create([
            'name' => 'Kritik Yelek',
            'quantity' => 8,
            'unit' => 'adet',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $ok = StockProduct::query()->create([
            'name' => 'Bol Kask',
            'quantity' => 40,
            'unit' => 'adet',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        StockProduct::query()->create([
            'name' => 'Tükenen Eldiven',
            'quantity' => 0,
            'unit' => 'çift',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        StockAssignment::query()->create([
            'stock_product_id' => $critical->id,
            'courier_id' => $courier->id,
            'quantity' => 2,
            'assigned_at' => now()->toDateString(),
            'status' => 'assigned',
            'assigned_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('Envanter Durumu')
            ->assertSee('Kritik / Tükenen Stok')
            ->assertSee('Kritik Yelek')
            ->assertSee('Tükenen Eldiven')
            ->assertSee('Bol Kask')
            ->assertSee('Kritik Stok')
            ->assertSee('Stokta Yok')
            ->assertSee('Zimmet Kurye')
            ->assertSee('Son Zimmetler');

        $this->assertSame(8, $critical->fresh()->quantity);
        $this->assertSame(40, $ok->fresh()->quantity);
    }

    public function test_super_admin_sees_stock_in_main_menu(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stok Yönetimi')
            ->assertSee(route('stock.dashboard'), false)
            ->assertSee(route('stock.products.index'), false)
            ->assertSee(route('stock.assignments.index'), false)
            ->assertSee(route('stock.activity.index'), false)
            ->assertSee('Envanter Durumu')
            ->assertSee('Kayıt Geçmişi');
    }

    public function test_operations_specialist_can_perform_full_stock_workflow(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');
        $courier = $this->createCourier($user);

        $this->assertTrue($user->can('stock.view'));
        $this->assertTrue($user->can('stock.create'));
        $this->assertTrue($user->can('stock.update'));
        $this->assertTrue($user->can('stock.delete'));

        $this->actingAs($user)
            ->post(route('stock.products.store'), [
                'name' => 'Operasyon Kaskı',
                'sku' => 'OPS-KSK',
                'description' => null,
                'quantity' => 4,
                'unit' => 'adet',
                'status' => 'active',
                'notes' => null,
            ])
            ->assertRedirect();

        $product = StockProduct::query()->where('sku', 'OPS-KSK')->firstOrFail();

        $this->actingAs($user)
            ->put(route('stock.products.update', $product->id), [
                'name' => 'Operasyon Kaskı',
                'sku' => 'OPS-KSK',
                'description' => null,
                'quantity' => 6,
                'unit' => 'adet',
                'status' => 'active',
                'notes' => null,
            ])
            ->assertRedirect(route('stock.products.show', $product->id));

        $this->actingAs($user)
            ->post(route('stock.products.assign', $product->id), [
                'courier_id' => $courier->id,
                'quantity' => 1,
                'assigned_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('stock.products.show', $product->id));

        $assignment = StockAssignment::query()->where('stock_product_id', $product->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('stock.assignments.return', $assignment->id))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('stock.activity.index'))
            ->assertOk()
            ->assertSee('Ürün Oluşturuldu')
            ->assertSee('Stok Artırıldı')
            ->assertSee('Zimmet Verildi')
            ->assertSee('Zimmet İade Alındı');
    }

    private function createCourier(User $user): Courier
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Courier::factory()->create([
            'created_by' => $user->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'full_name' => 'Zimmet Kurye',
            'status' => 'active',
        ]);
    }
}
