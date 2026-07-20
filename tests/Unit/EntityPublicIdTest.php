<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityPublicIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            CitySeeder::class,
        ]);
    }

    public function test_public_ids_are_unique_across_business_courier_and_agency(): void
    {
        $user = User::factory()->create();

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'public_id' => '12345678',
        ]);
        $courier = Courier::factory()->create([
            'created_by' => $user->id,
        ]);
        $agency = Agency::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->assertNotSame($business->public_id, $courier->public_id);
        $this->assertNotSame($business->public_id, $agency->public_id);
        $this->assertNotSame($courier->public_id, $agency->public_id);
        $this->assertTrue(Business::publicIdIsTaken('12345678'));
        $this->assertTrue(Courier::publicIdIsTaken((string) $courier->public_id));
    }

    public function test_cannot_reuse_business_public_id_on_courier(): void
    {
        $user = User::factory()->create();

        Business::factory()->create([
            'created_by' => $user->id,
            'public_id' => '87654321',
        ]);

        $this->expectException(\RuntimeException::class);

        Courier::factory()->create([
            'created_by' => $user->id,
            'public_id' => '87654321',
        ]);
    }
}
