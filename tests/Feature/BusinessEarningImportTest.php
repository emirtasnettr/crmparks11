<?php

namespace Tests\Feature;

use App\Core\Exports\TabularExport;
use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Services\BusinessEarningImportService;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class BusinessEarningImportTest extends TestCase
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

    public function test_template_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('businesses.earnings.template'))
            ->assertForbidden();
    }

    public function test_template_downloads_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('businesses.earnings.template'))
            ->assertOk()
            ->assertDownload();
    }

    public function test_import_requires_permission(): void
    {
        $user = User::factory()->create();
        $file = $this->makeImportFile([['isletme_id', 'kurye_id']]);

        $this->actingAs($user)
            ->post(route('businesses.earnings.import'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_import_creates_earnings_from_excel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $template = app(BusinessEarningImportService::class)->templateSheet();
        $file = $this->makeImportFile(array_merge(
            [$template['headings']],
            [
                [$business->id, $courier->id, 7, 2026, 'per_package', 100, 50, 40, '', '', 0, 0, 0, 'Toplu paket', 'draft'],
                [$business->id, $courier->id, 7, 2026, 'monthly_fixed', '', '', '', 15000, 12000, 0, 0, 0, 'Toplu sabit', 'pending'],
            ],
        ));

        $response = $this->actingAs($user)->post(route('businesses.earnings.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('businesses.earnings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('earning_lines', 2);
        $this->assertDatabaseHas('earning_lines', [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'description' => 'Toplu paket',
        ]);
        $this->assertDatabaseHas('earning_lines', [
            'business_id' => $business->id,
            'description' => 'Toplu sabit',
        ]);
    }

    public function test_import_skips_invalid_rows_and_keeps_valid_ones(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $template = app(BusinessEarningImportService::class)->templateSheet();
        $file = $this->makeImportFile(array_merge(
            [$template['headings']],
            [
                [$business->id, $courier->id, 7, 2026, 'per_package', 10, 50, 40, '', '', 0, 0, 0, 'Geçerli', 'draft'],
                [99999, $courier->id, 7, 2026, 'per_package', 10, 50, 40, '', '', 0, 0, 0, 'Geçersiz işletme', 'draft'],
            ],
        ));

        $response = $this->actingAs($user)->post(route('businesses.earnings.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('businesses.earnings.index'));
        $response->assertSessionHas('import_errors');
        $this->assertDatabaseCount('earning_lines', 1);
        $this->assertDatabaseHas('earning_lines', [
            'description' => 'Geçerli',
        ]);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeImportFile(array $rows): UploadedFile
    {
        $headings = array_shift($rows);
        $filename = 'earning-import-'.uniqid('', true).'.xlsx';

        Excel::store(
            new TabularExport($headings, $rows, 'Hakediş'),
            $filename,
            'local',
        );

        $stored = Storage::disk('local')->path($filename);

        return new UploadedFile($stored, 'hakedis.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
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
