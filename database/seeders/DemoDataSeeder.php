<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Contract;
use App\Models\District;
use App\Models\Document;
use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\Neighborhood;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use App\Modules\Courier\Models\CourierVehicle;
use App\Modules\Courier\Services\CourierUserProvisioner;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\Stock\Models\StockAssignment;
use App\Modules\Stock\Models\StockProduct;
use App\Support\DemoDataCleaner;
use App\Support\DemoDataGuard;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Yerel/test örnek verisi. DatabaseSeeder'a eklenmez; yalnızca crmlog:seed-demo ile çalışır.
 * Production'da DemoDataGuard tarafından engellenir.
 *
 * Temizle: php artisan crmlog:clear-demo
 */
class DemoDataSeeder extends Seeder
{
    public const MARKER = 'DEMO_SEED';

    /** @var list<array{first: string, last: string}> */
    private const COURIER_NAMES = [
        ['first' => 'Ahmet', 'last' => 'Yılmaz'],
        ['first' => 'Mehmet', 'last' => 'Demir'],
        ['first' => 'Ayşe', 'last' => 'Kaya'],
        ['first' => 'Fatma', 'last' => 'Çelik'],
        ['first' => 'Mustafa', 'last' => 'Şahin'],
        ['first' => 'Emine', 'last' => 'Arslan'],
        ['first' => 'Ali', 'last' => 'Doğan'],
        ['first' => 'Zeynep', 'last' => 'Aydın'],
        ['first' => 'Hüseyin', 'last' => 'Öztürk'],
        ['first' => 'Elif', 'last' => 'Yıldız'],
        ['first' => 'İbrahim', 'last' => 'Koç'],
        ['first' => 'Selin', 'last' => 'Acar'],
        ['first' => 'Murat', 'last' => 'Polat'],
        ['first' => 'Deniz', 'last' => 'Kurt'],
        ['first' => 'Canan', 'last' => 'Erdoğan'],
        ['first' => 'Burak', 'last' => 'Güneş'],
        ['first' => 'Hatice', 'last' => 'Aslan'],
        ['first' => 'Emre', 'last' => 'Çetin'],
        ['first' => 'Baran', 'last' => 'Özkan'],
        ['first' => 'Gizem', 'last' => 'Bulut'],
        ['first' => 'Onur', 'last' => 'Taş'],
        ['first' => 'Pınar', 'last' => 'Aksoy'],
        ['first' => 'Tolga', 'last' => 'Sezer'],
        ['first' => 'Ceren', 'last' => 'Duman'],
        ['first' => 'Kerem', 'last' => 'Yalçın'],
        ['first' => 'Melisa', 'last' => 'Koçak'],
        ['first' => 'Oğuz', 'last' => 'Bayrak'],
        ['first' => 'Sude', 'last' => 'Işık'],
        ['first' => 'Volkan', 'last' => 'Akın'],
        ['first' => 'Nazlı', 'last' => 'Erdem'],
        ['first' => 'Berk', 'last' => 'Uçar'],
        ['first' => 'İrem', 'last' => 'Sarı'],
        ['first' => 'Furkan', 'last' => 'Bilgin'],
        ['first' => 'Ebru', 'last' => 'Tekin'],
        ['first' => 'Yiğit', 'last' => 'Karaca'],
        ['first' => 'Derya', 'last' => 'Özer'],
        ['first' => 'Sinan', 'last' => 'Güler'],
        ['first' => 'Aslı', 'last' => 'Tunç'],
        ['first' => 'Merve', 'last' => 'Kara'],
        ['first' => 'Serkan', 'last' => 'Yavuz'],
    ];

    public function run(): void
    {
        DemoDataGuard::assertAllowed();

        if (Business::withTrashed()->where('notes', self::MARKER)->exists()) {
            DemoDataCleaner::clear();
        }

        if (! City::query()->exists()) {
            $this->call(CitySeeder::class);
        }

        if (! Neighborhood::query()->exists()) {
            $this->call(NeighborhoodSeeder::class);
        }

        if (! EarningStatus::query()->exists()) {
            $this->call(LookupTableSeeder::class);
        }

        if (\Spatie\Permission\Models\Role::query()->doesntExist()) {
            $this->call(RoleAndPermissionSeeder::class);
        }

        $this->call(AdminUserSeeder::class);

        fake()->unique(true);

        $admin = User::query()->where('email', 'admin@crmlog.com')->firstOrFail();

        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        DB::transaction(function () use ($admin): void {
            $city = City::query()->where('name', 'İstanbul')->first()
                ?? City::query()->orderBy('id')->firstOrFail();
            $district = District::query()->where('city_id', $city->id)->orderBy('id')->first()
                ?? District::query()->orderBy('id')->firstOrFail();

            $agencies = $this->seedAgencies($admin, $city, $district);
            $businesses = $this->seedBusinesses($admin, $city, $district);
            $couriers = $this->seedCouriers($admin, $agencies);
            $roster = $this->allocateDemoRoster($businesses, $couriers);
            $this->seedShifts($admin, $businesses, $roster);
            $this->seedStock($admin, $couriers);
            $this->seedHourlyEarningsFromAttendances($admin);
            $this->seedEarningsAndFinance($admin, $businesses, $couriers, $roster, $agencies);
        });

        $opening = Business::query()->where('notes', self::MARKER)->where('status', 'opening_stage')->count();
        $active = Business::query()->where('notes', self::MARKER)->where('status', 'active')->count();
        $courierCount = Courier::query()->where('notes', self::MARKER)->count();
        $shifts = BusinessShift::query()->where('notes', self::MARKER)->count();
        $attendances = BusinessShiftAttendance::query()->where('notes', self::MARKER)->count();
        $earnings = EarningLine::query()->where('description', 'like', self::MARKER.'%')->count();
        $stock = StockProduct::query()->where('notes', self::MARKER)->count();

        $this->command?->info(
            "Demo veri hazır: {$active} aktif / {$opening} açılış işletme, "
            ."{$courierCount} kurye, {$shifts} vardiya, {$attendances} katılım, "
            ."{$earnings} hakediş, {$stock} stok ürünü + finans. "
            .'Vardiyalar '.now()->format('Y-m').' ayı (1 → ay sonu); canlı pano: aktif / geç / girmedi.'
        );
    }

    /**
     * @return list<Agency>
     */
    private function seedAgencies(User $admin, City $city, District $district): array
    {
        $defs = [
            [
                'company_name' => 'Hızlı Rota Lojistik A.Ş.',
                'brand_name' => 'Hızlı Rota',
                'tax_number' => '9000000001',
                'status' => 'active',
                'authorized_person' => 'Cemal Aksoy',
            ],
            [
                'company_name' => 'Anadolu Kurye Hizmetleri Ltd. Şti.',
                'brand_name' => 'Anadolu Kurye',
                'tax_number' => '9000000002',
                'status' => 'active',
                'authorized_person' => 'Sevgi Demirtaş',
            ],
            [
                'company_name' => 'Marmara Dağıtım Acentesi Ltd.',
                'brand_name' => 'Marmara Express',
                'tax_number' => '9000000003',
                'status' => 'pending',
                'authorized_person' => 'Okan Yücel',
            ],
        ];

        $agencies = [];

        foreach ($defs as $def) {
            $agency = Agency::factory()->create([
                ...$def,
                'city_id' => $city->id,
                'district_id' => $district->id,
                'created_by' => $admin->id,
                'notes' => self::MARKER,
            ]);

            AgencyContact::factory()->create([
                'agency_id' => $agency->id,
                'full_name' => $def['authorized_person'],
                'title' => 'Operasyon Müdürü',
                'is_default' => true,
                'status' => 'active',
            ]);

            if ($agency->status === 'active') {
                Contract::factory()->create([
                    'contractable_type' => Agency::class,
                    'contractable_id' => $agency->id,
                    'title' => $agency->brand_name.' Acentelik Sözleşmesi',
                    'created_by' => $admin->id,
                    'status' => 'active',
                ]);
            }

            $agencies[] = $agency;
        }

        return $agencies;
    }

    /**
     * @return list<Business>
     */
    private function seedBusinesses(User $admin, City $city, District $district): array
    {
        $defs = [
            // Aktif — dashboard / listeler / finans
            [
                'company_name' => 'Ateş Odun Restoranları A.Ş.',
                'brand_name' => 'Ateş & Odun',
                'tax_number' => '9000001001',
                'status' => 'active',
                'planned_courier_count' => 6,
                'earning_period' => 'weekly',
                'assign' => 7,
            ],
            [
                'company_name' => 'Pizza Locale Gıda Ltd. Şti.',
                'brand_name' => 'Pizza Locale',
                'tax_number' => '9000001002',
                'status' => 'active',
                'planned_courier_count' => 5,
                'earning_period' => 'weekly',
                'assign' => 6,
            ],
            [
                'company_name' => 'Kahve Rengi İşletmeleri A.Ş.',
                'brand_name' => 'Kahve Rengi',
                'tax_number' => '9000001003',
                'status' => 'active',
                'planned_courier_count' => 4,
                'earning_period' => 'biweekly',
                'assign' => 6,
            ],
            [
                'company_name' => 'Nori Sushi İstanbul Ltd.',
                'brand_name' => 'Nori Sushi',
                'tax_number' => '9000001004',
                'status' => 'active',
                'planned_courier_count' => 3,
                'earning_period' => 'monthly',
                'assign' => 6,
            ],
            [
                'company_name' => 'Döneristan Gıda A.Ş.',
                'brand_name' => 'Döneristan',
                'tax_number' => '9000001005',
                'status' => 'active',
                'planned_courier_count' => 8,
                'earning_period' => 'weekly',
                'assign' => 7,
            ],
            [
                'company_name' => 'Yeşil Bowl Healthy Food Ltd.',
                'brand_name' => 'Yeşil Bowl',
                'tax_number' => '9000001006',
                'status' => 'active',
                'planned_courier_count' => 4,
                'earning_period' => 'weekly',
                'assign' => 6,
            ],

            // Açılış aşaması — dashboard “Açılış Aşamasındakiler”
            [
                'company_name' => 'Şekerci Han Pastaneleri Ltd.',
                'brand_name' => 'Şekerci Han',
                'tax_number' => '9000001007',
                'status' => 'opening_stage',
                'start_date' => now()->addDay()->toDateString(),
                'planned_courier_count' => 5,
                'earning_period' => 'weekly',
                'assign' => 1,
            ],
            [
                'company_name' => 'Anne Sofrası Yemekleri A.Ş.',
                'brand_name' => 'Anne Sofrası',
                'tax_number' => '9000001008',
                'status' => 'opening_stage',
                'start_date' => now()->addDays(4)->toDateString(),
                'planned_courier_count' => 8,
                'earning_period' => 'weekly',
                'assign' => 2,
            ],
            [
                'company_name' => 'Ali Baba Çiğköfte Ltd. Şti.',
                'brand_name' => 'Ali Baba',
                'tax_number' => '9000001009',
                'status' => 'opening_stage',
                'start_date' => now()->addDays(12)->toDateString(),
                'planned_courier_count' => 6,
                'earning_period' => 'biweekly',
                'assign' => 1,
            ],
            [
                'company_name' => 'Balıkçı Liman Deniz Ürünleri A.Ş.',
                'brand_name' => 'Balıkçı Liman',
                'tax_number' => '9000001010',
                'status' => 'opening_stage',
                'start_date' => now()->addDays(20)->toDateString(),
                'planned_courier_count' => 4,
                'earning_period' => 'weekly',
                'assign' => 1,
            ],

            // Sözleşme / beklemede / pasif
            [
                'company_name' => 'Mantı Evi Restoranları Ltd.',
                'brand_name' => 'Mantı Evi',
                'tax_number' => '9000001011',
                'status' => 'contract_stage',
                'estimated_opening_date' => now()->addMonths(1)->toDateString(),
                'planned_courier_count' => 5,
                'earning_period' => 'weekly',
                'assign' => 0,
            ],
            [
                'company_name' => 'Börekçi Usta Gıda Ltd.',
                'brand_name' => 'Börekçi Usta',
                'tax_number' => '9000001012',
                'status' => 'pending',
                'estimated_opening_date' => now()->addMonths(2)->toDateString(),
                'planned_courier_count' => 3,
                'earning_period' => 'weekly',
                'assign' => 0,
            ],
            [
                'company_name' => 'Meze Bar İstanbul A.Ş.',
                'brand_name' => 'Meze Bar',
                'tax_number' => '9000001013',
                'status' => 'inactive',
                'contract_end_date' => now()->subMonths(1)->toDateString(),
                'planned_courier_count' => 4,
                'earning_period' => 'monthly',
                'assign' => 0,
            ],
        ];

        $businesses = [];

        foreach ($defs as $def) {
            $assignCount = $def['assign'];
            unset($def['assign']);

            $business = Business::factory()->create([
                ...$def,
                'city_id' => $city->id,
                'district_id' => $district->id,
                'created_by' => $admin->id,
                'notes' => self::MARKER,
                'phone' => '0212 '.fake()->numerify('### ## ##'),
                'email' => strtolower(str_replace([' ', '&'], ['', ''], $def['brand_name'])).'@demo.crmlog.local',
                'tax_office' => 'Kadıköy Vergi Dairesi',
                'address' => fake()->numerify('Caferağa Mah. Moda Cad. No:## / #, Kadıköy / İstanbul'),
            ]);

            BusinessContact::factory()->create([
                'business_id' => $business->id,
                'full_name' => fake()->randomElement(['Kemal Özer', 'Seda Akın', 'Yusuf Erdem', 'Pınar Gül']),
                'title' => 'İşletme Sahibi',
                'is_default' => true,
                'status' => 'active',
            ]);

            if (in_array($business->status, ['active', 'opening_stage', 'contract_stage'], true)) {
                Contract::factory()->create([
                    'contractable_type' => Business::class,
                    'contractable_id' => $business->id,
                    'title' => $business->brand_name.' Hizmet Sözleşmesi',
                    'created_by' => $admin->id,
                    'status' => $business->status === 'contract_stage' ? 'draft' : 'active',
                ]);
            }

            if (in_array($business->status, ['active', 'opening_stage'], true)) {
                Document::factory()->create([
                    'documentable_type' => Business::class,
                    'documentable_id' => $business->id,
                    'original_name' => 'vergi-levhasi-'.$business->tax_number.'.pdf',
                    'uploaded_by' => $admin->id,
                ]);
            }

            if (in_array($business->brand_name, ['Ateş & Odun', 'Pizza Locale'], true)) {
                $contract = $business->activeCommercialContract;
                if ($contract !== null) {
                    $contract->update([
                        'work_type' => \App\Modules\Business\Models\BusinessCommercialContract::WORK_HOURLY,
                        'business_amount' => 180,
                        'courier_amount' => 120,
                        'net_profit' => 60,
                        'guaranteed_hourly_package_fee' => null,
                    ]);
                }
            }

            $business->setAttribute('_demo_assign_count', $assignCount);
            $businesses[] = $business;
        }

        return $businesses;
    }

    /**
     * @param  list<Agency>  $agencies
     * @return list<Courier>
     */
    private function seedCouriers(User $admin, array $agencies): array
    {
        $activeAgencies = array_values(array_filter(
            $agencies,
            fn (Agency $agency): bool => $agency->status === 'active'
        ));

        $couriers = [];
        $vehicleTypes = ['motorcycle', 'motorcycle', 'ebike', 'car', 'bicycle'];

        foreach (self::COURIER_NAMES as $index => $name) {
            $n = $index + 1;
            $isAgency = $n <= 10;
            $agency = $isAgency ? $activeAgencies[($n - 1) % count($activeAgencies)] : null;
            $status = match (true) {
                $n === 39 => 'on_leave',
                $n === 40 => 'inactive',
                default => 'active',
            };

            $courier = Courier::factory()->create([
                'first_name' => $name['first'],
                'last_name' => $name['last'],
                'full_name' => $name['first'].' '.$name['last'],
                'courier_type' => $isAgency ? 'agency' : 'independent',
                'agency_id' => $agency?->id,
                'created_by' => $admin->id,
                'status' => $status,
                'tc_number' => sprintf('9000000%04d', $n),
                'phone' => '05'.fake()->numerify('## ### ## ##'),
                'email' => sprintf('%s.%s@demo.crmlog.local',
                    $this->slugTr($name['first']),
                    $this->slugTr($name['last'])
                ),
                'notes' => self::MARKER,
                'start_date' => now()->subMonths(fake()->numberBetween(1, 18))->toDateString(),
            ]);

            app(CourierUserProvisioner::class)->ensureForCourier($courier);

            CourierVehicle::factory()->create([
                'courier_id' => $courier->id,
                'vehicle_type' => $vehicleTypes[$index % count($vehicleTypes)],
            ]);

            CourierBankAccount::factory()->create([
                'courier_id' => $courier->id,
                'is_default' => true,
            ]);

            $couriers[] = $courier;
        }

        return $couriers;
    }

    /**
     * İşletme → kurye eşlemesi (vardiya kadrosu için; BCA yok).
     *
     * @param  list<Business>  $businesses
     * @param  list<Courier>  $couriers
     * @return list<array{business_id: int, courier_id: int}>
     */
    private function allocateDemoRoster(array $businesses, array $couriers): array
    {
        $activeCouriers = array_values(array_filter(
            $couriers,
            fn (Courier $courier): bool => $courier->status === 'active'
        ));

        $roster = [];
        $cursor = 0;
        $poolSize = count($activeCouriers);

        foreach ($businesses as $business) {
            $need = (int) $business->getAttribute('_demo_assign_count');
            if ($need <= 0) {
                continue;
            }

            for ($i = 0; $i < $need; $i++) {
                if ($cursor >= $poolSize) {
                    break 2;
                }

                $courier = $activeCouriers[$cursor];
                $cursor++;

                $roster[] = [
                    'business_id' => $business->id,
                    'courier_id' => $courier->id,
                ];
            }
        }

        return $roster;
    }

    /**
     * @param  list<Business>  $businesses
     * @param  list<array{business_id: int, courier_id: int}>  $roster
     */
    private function seedShifts(User $admin, array $businesses, array $roster): void
    {
        $activeBusinesses = array_values(array_filter(
            $businesses,
            fn (Business $business): bool => $business->status === 'active'
        ));

        $openingBusinesses = array_values(array_filter(
            $businesses,
            fn (Business $business): bool => $business->status === 'opening_stage'
        ));

        $targets = array_slice(array_merge(
            array_slice($activeBusinesses, 0, 4),
            array_slice($openingBusinesses, 0, 2),
        ), 0, 6);

        $shiftDefs = [
            [
                'name' => 'Öğle Vardiyası',
                'start_time' => '10:00',
                'end_time' => '16:00',
                'required_headcount' => 3,
                'days_of_week' => [1, 2, 3, 4, 5, 6],
            ],
            [
                'name' => 'Akşam Vardiyası',
                'start_time' => '16:00',
                'end_time' => '23:00',
                'required_headcount' => 4,
                'days_of_week' => [1, 2, 3, 4, 5, 6, 0],
            ],
        ];

        $createdShifts = [];

        foreach ($targets as $businessIndex => $business) {
            $businessCourierIds = collect($roster)
                ->filter(fn (array $row): bool => $row['business_id'] === $business->id)
                ->pluck('courier_id')
                ->unique()
                ->values()
                ->all();

            foreach ($shiftDefs as $defIndex => $def) {
                if ($business->status === 'opening_stage' && $defIndex === 1) {
                    continue;
                }

                $available = count($businessCourierIds);
                $wanted = (int) $def['required_headcount'];
                $required = max(1, min($wanted, $available));

                $shift = BusinessShift::query()->create([
                    'business_id' => $business->id,
                    'name' => $def['name'],
                    'start_time' => $def['start_time'],
                    'end_time' => $def['end_time'],
                    'required_headcount' => max(1, $required),
                    'start_date' => now()->copy()->startOfMonth()->toDateString(),
                    'end_date' => now()->copy()->endOfMonth()->toDateString(),
                    'days_of_week' => $def['days_of_week'],
                    'excluded_dates' => [],
                    'notes' => self::MARKER,
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]);

                $shiftRoster = array_slice($businessCourierIds, 0, $shift->required_headcount);
                foreach ($shiftRoster as $courierId) {
                    BusinessShiftCourier::query()->create([
                        'business_shift_id' => $shift->id,
                        'courier_id' => $courierId,
                    ]);
                }

                $createdShifts[] = [
                    'shift' => $shift,
                    'roster' => $shiftRoster,
                    'business' => $business,
                    'business_courier_ids' => $businessCourierIds,
                    'business_index' => $businessIndex,
                    'def_index' => $defIndex,
                ];
            }
        }

        $this->seedShiftAttendances($createdShifts);
        $this->seedLiveOperationsBoard($admin, $businesses, $roster);
    }

    /**
     * Canlı Operasyon panosu için bugüne sabit dağılım:
     * 5 girmedi · 7 geç kaldı · 17 aktif · 7 yaklaşan
     *
     * @param  list<Business>  $businesses
     * @param  list<array{business_id: int, courier_id: int}>  $roster
     */
    private function seedLiveOperationsBoard(User $admin, array $businesses, array $roster): void
    {
        $today = now()->toDateString();

        // Diğer demo vardiyaları bugünden çıkar; pano sayıları net kalsın.
        BusinessShift::query()
            ->where('notes', self::MARKER)
            ->each(function (BusinessShift $shift) use ($today): void {
                $excluded = collect($shift->excluded_dates ?? [])
                    ->map(fn ($value) => Carbon::parse((string) $value)->toDateString())
                    ->push($today)
                    ->unique()
                    ->values()
                    ->all();

                $shift->update(['excluded_dates' => $excluded]);
            });

        $courierBusiness = collect($roster)
            ->mapWithKeys(fn (array $row) => [(int) $row['courier_id'] => (int) $row['business_id']]);

        $courierIds = $courierBusiness->keys()->values()->all();
        if (count($courierIds) < 36) {
            return;
        }

        $now = now()->seconds(0)->microseconds(0);
        $currentStart = $now->copy()->subHours(2);
        $currentEnd = $now->copy()->addHours(5);
        $soonStart = $now->copy()->addMinutes(35);
        $soonEnd = $soonStart->copy()->addHours(6);

        $notStartedIds = array_slice($courierIds, 0, 5);
        $lateIds = array_slice($courierIds, 5, 7);
        $activeIds = array_slice($courierIds, 12, 17);
        $soonIds = array_slice($courierIds, 29, 7);

        $currentRoster = [...$notStartedIds, ...$lateIds, ...$activeIds];

        $groups = [];
        foreach ($currentRoster as $courierId) {
            $businessId = $courierBusiness->get($courierId);
            if ($businessId === null) {
                continue;
            }
            $groups[$businessId]['current'][] = $courierId;
        }
        foreach ($soonIds as $courierId) {
            $businessId = $courierBusiness->get($courierId);
            if ($businessId === null) {
                continue;
            }
            $groups[$businessId]['soon'][] = $courierId;
        }

        $businessById = collect($businesses)->keyBy('id');

        foreach ($groups as $businessId => $rosters) {
            /** @var Business|null $business */
            $business = $businessById->get($businessId)?->fresh(['activeCommercialContract']);
            if ($business === null) {
                continue;
            }

            $contract = $business->activeCommercialContract;
            $pricingCode = $contract?->work_type;
            $hourlyRate = $pricingCode === 'hourly' ? (float) $contract?->courier_amount : null;

            if (! empty($rosters['current'])) {
                $shift = BusinessShift::query()->create([
                    'business_id' => $business->id,
                    'name' => 'Canlı Operasyon',
                    'start_time' => $currentStart->format('H:i'),
                    'end_time' => $currentEnd->format('H:i'),
                    'required_headcount' => count($rosters['current']),
                    'start_date' => $today,
                    'end_date' => $today,
                    'days_of_week' => [(int) now()->dayOfWeek],
                    'excluded_dates' => [],
                    'notes' => self::MARKER,
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]);

                foreach ($rosters['current'] as $courierId) {
                    BusinessShiftCourier::query()->create([
                        'business_shift_id' => $shift->id,
                        'courier_id' => $courierId,
                    ]);
                }

                foreach ($lateIds as $index => $courierId) {
                    if (! in_array($courierId, $rosters['current'], true)) {
                        continue;
                    }

                    BusinessShiftAttendance::query()->create([
                        'business_shift_id' => $shift->id,
                        'business_id' => $business->id,
                        'courier_id' => $courierId,
                        'work_date' => $today,
                        'started_at' => $currentStart->copy()->addMinutes(12 + ($index * 3)),
                        'ended_at' => null,
                        'status' => 'in_progress',
                        'worked_minutes' => 0,
                        'hourly_rate' => $hourlyRate,
                        'earnings_amount' => null,
                        'pricing_model' => $pricingCode,
                        'notes' => self::MARKER,
                    ]);
                }

                foreach ($activeIds as $index => $courierId) {
                    if (! in_array($courierId, $rosters['current'], true)) {
                        continue;
                    }

                    BusinessShiftAttendance::query()->create([
                        'business_shift_id' => $shift->id,
                        'business_id' => $business->id,
                        'courier_id' => $courierId,
                        'work_date' => $today,
                        'started_at' => $currentStart->copy()->subMinutes(min(10, $index % 6)),
                        'ended_at' => null,
                        'status' => 'in_progress',
                        'worked_minutes' => 0,
                        'hourly_rate' => $hourlyRate,
                        'earnings_amount' => null,
                        'pricing_model' => $pricingCode,
                        'notes' => self::MARKER,
                    ]);
                }
            }

            if (! empty($rosters['soon'])) {
                $shift = BusinessShift::query()->create([
                    'business_id' => $business->id,
                    'name' => 'Yaklaşan Operasyon',
                    'start_time' => $soonStart->format('H:i'),
                    'end_time' => $soonEnd->format('H:i'),
                    'required_headcount' => count($rosters['soon']),
                    'start_date' => $today,
                    'end_date' => $today,
                    'days_of_week' => [(int) now()->dayOfWeek],
                    'excluded_dates' => [],
                    'notes' => self::MARKER,
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]);

                foreach ($rosters['soon'] as $courierId) {
                    BusinessShiftCourier::query()->create([
                        'business_shift_id' => $shift->id,
                        'courier_id' => $courierId,
                    ]);
                }
            }
        }
    }

    /**
     * @param  list<array{shift: BusinessShift, roster: list<int>, business: Business, business_courier_ids: list<int>, business_index: int, def_index: int}>  $createdShifts
     */
    private function seedShiftAttendances(array $createdShifts): void
    {
        $monthStart = now()->copy()->startOfMonth()->startOfDay();
        $yesterday = now()->copy()->subDay()->startOfDay();

        if ($yesterday->lt($monthStart)) {
            return;
        }

        foreach ($createdShifts as $item) {
            /** @var BusinessShift $shift */
            $shift = $item['shift'];
            /** @var Business $business */
            $business = $item['business']->fresh(['activeCommercialContract']);
            $contract = $business->activeCommercialContract;
            $pricingCode = $contract?->work_type;
            $hourlyRate = $pricingCode === 'hourly'
                ? (float) $contract?->courier_amount
                : ($pricingCode === 'per_package'
                    ? (float) ($contract?->guaranteed_hourly_package_fee ?: $contract?->courier_amount)
                    : null);

            $startHour = (int) substr((string) $shift->start_time, 0, 2);
            $startMinute = (int) substr((string) $shift->start_time, 3, 2);
            $endHour = (int) substr((string) $shift->end_time, 0, 2);
            $endMinute = (int) substr((string) $shift->end_time, 3, 2);

            $plannedMinutes = (($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute) + 1440) % 1440;
            if ($plannedMinutes <= 0) {
                $plannedMinutes = 360;
            }

            for ($day = $monthStart->copy(); $day->lte($yesterday); $day->addDay()) {
                if (! $shift->runsOn($day)) {
                    continue;
                }

                $workingIds = array_values($item['roster']);
                $dayIndex = (int) $monthStart->diffInDays($day);

                foreach ($workingIds as $courierIndex => $courierId) {
                    // Gerçekçi boşluk: bazı günlerde bazı kuryeler gelmemiş olsun.
                    if (($dayIndex + $courierIndex + $item['def_index']) % 7 === 0) {
                        continue;
                    }

                    $lateMinutes = ($courierIndex * 3) % 12;
                    $earlyLeave = ($dayIndex % 5 === 0) ? 15 : 0;
                    $workedMinutes = max(60, $plannedMinutes - $earlyLeave);
                    $startedAt = $day->copy()->setTime($startHour, $startMinute)->addMinutes($lateMinutes);
                    $endedAt = $startedAt->copy()->addMinutes($workedMinutes);
                    $earnings = $hourlyRate !== null
                        ? round(($workedMinutes / 60) * $hourlyRate, 2)
                        : null;

                    BusinessShiftAttendance::query()->create([
                        'business_shift_id' => $shift->id,
                        'business_id' => $business->id,
                        'commercial_contract_id' => $contract?->id,
                        'courier_id' => $courierId,
                        'work_date' => $day->toDateString(),
                        'started_at' => $startedAt,
                        'ended_at' => $endedAt,
                        'status' => 'completed',
                        'worked_minutes' => $workedMinutes,
                        'hourly_rate' => $hourlyRate,
                        'earnings_amount' => $earnings,
                        'pricing_model' => $pricingCode,
                        'notes' => self::MARKER,
                    ]);
                }
            }
        }
    }

    /**
     * @param  list<Courier>  $couriers
     */
    private function seedStock(User $admin, array $couriers): void
    {
        $activeCouriers = array_values(array_filter(
            $couriers,
            fn (Courier $courier): bool => $courier->status === 'active'
        ));

        $products = [
            ['name' => 'Termal Çanta (Büyük)', 'sku' => 'DEMO-CAG-001', 'quantity' => 40, 'unit' => 'adet'],
            ['name' => 'Termal Çanta (Küçük)', 'sku' => 'DEMO-CAG-002', 'quantity' => 25, 'unit' => 'adet'],
            ['name' => 'Yağmurluk', 'sku' => 'DEMO-YAG-001', 'quantity' => 30, 'unit' => 'adet'],
            ['name' => 'Powerbank', 'sku' => 'DEMO-PWR-001', 'quantity' => 20, 'unit' => 'adet'],
            ['name' => 'Kurye Yeleği', 'sku' => 'DEMO-YEL-001', 'quantity' => 50, 'unit' => 'adet'],
        ];

        foreach ($products as $index => $def) {
            $product = StockProduct::query()->create([
                'name' => $def['name'],
                'sku' => $def['sku'],
                'description' => 'Demo stok kalemi',
                'quantity' => $def['quantity'],
                'unit' => $def['unit'],
                'status' => 'active',
                'notes' => self::MARKER,
                'created_by' => $admin->id,
            ]);

            if ($index < 3 && isset($activeCouriers[$index])) {
                StockAssignment::query()->create([
                    'stock_product_id' => $product->id,
                    'courier_id' => $activeCouriers[$index]->id,
                    'quantity' => 1,
                    'assigned_at' => now()->subDays(5)->toDateString(),
                    'status' => 'assigned',
                    'notes' => self::MARKER,
                    'assigned_by' => $admin->id,
                ]);

                $product->update(['quantity' => max(0, $def['quantity'] - 1)]);
            }
        }
    }

    private function seedHourlyEarningsFromAttendances(User $admin): void
    {
        $statusIds = EarningStatus::query()->pluck('id', 'code');

        $groups = BusinessShiftAttendance::query()
            ->where('notes', self::MARKER)
            ->where('status', 'completed')
            ->where('pricing_model', 'hourly')
            ->whereNotNull('earnings_amount')
            ->get()
            ->groupBy(function (BusinessShiftAttendance $attendance): string {
                $month = $attendance->work_date->format('n');
                $year = $attendance->work_date->format('Y');

                return "{$attendance->business_id}:{$attendance->courier_id}:{$year}:{$month}";
            });

        $statusByAge = [
            0 => 'pending_review',
            1 => 'approved',
            2 => 'paid',
        ];

        foreach ($groups as $rows) {
            /** @var BusinessShiftAttendance $sample */
            $sample = $rows->first();
            $periodMonth = (int) $sample->work_date->format('n');
            $periodYear = (int) $sample->work_date->format('Y');
            $monthsAgo = ((int) now()->format('Y') * 12 + (int) now()->format('n'))
                - ($periodYear * 12 + $periodMonth);
            $statusCode = $statusByAge[min(2, max(0, $monthsAgo))] ?? 'draft';

            $workedHours = round($rows->sum('worked_minutes') / 60, 2);
            $courierUnit = (float) ($rows->avg('hourly_rate') ?: 0);
            $business = Business::query()->with('activeCommercialContract')->find($sample->business_id);
            $revenueUnit = (float) ($business?->activeCommercialContract?->business_amount ?: $courierUnit * 1.5);
            $courierTotal = round((float) $rows->sum('earnings_amount'), 2);
            $revenueTotal = round($workedHours * $revenueUnit, 2);

            EarningLine::factory()->create([
                'business_id' => $sample->business_id,
                'courier_id' => $sample->courier_id,
                'business_pricing_id' => null,
                'earning_type' => 'hourly',
                'pricing_model' => 'hourly',
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'package_count' => 0,
                'worked_hours' => $workedHours,
                'revenue_unit_price' => $revenueUnit,
                'revenue_total' => $revenueTotal,
                'courier_unit_price' => $courierUnit,
                'courier_total' => $courierTotal,
                'agency_payment' => 0,
                'net_courier_payment' => $courierTotal,
                'profit' => round($revenueTotal - $courierTotal, 2),
                'status_id' => $statusIds[$statusCode] ?? $statusIds['draft'],
                'created_by' => $admin->id,
                'description' => self::MARKER.' · Saatlik vardiya hakedişi ('.$workedHours.' sa)',
            ]);
        }

        // Gelecek dönem taslak hakediş (henüz onaylanmamış plan).
        $hourlyBusinessIds = BusinessShiftAttendance::query()
            ->where('notes', self::MARKER)
            ->where('pricing_model', 'hourly')
            ->distinct()
            ->pluck('business_id');

        $nextMonth = now()->addMonth();
        foreach ($hourlyBusinessIds->take(2) as $businessId) {
            $courierId = BusinessShiftAttendance::query()
                ->where('notes', self::MARKER)
                ->where('business_id', $businessId)
                ->where('pricing_model', 'hourly')
                ->value('courier_id');

            if ($courierId === null) {
                continue;
            }

            $business = Business::query()->with('activeCommercialContract')->find($businessId);
            $courierUnit = (float) ($business?->activeCommercialContract?->courier_amount ?: 120);
            $revenueUnit = (float) ($business?->activeCommercialContract?->business_amount ?: 180);
            $plannedHours = 96.0;

            EarningLine::factory()->create([
                'business_id' => $businessId,
                'courier_id' => $courierId,
                'business_pricing_id' => null,
                'earning_type' => 'hourly',
                'pricing_model' => 'hourly',
                'period_month' => (int) $nextMonth->format('n'),
                'period_year' => (int) $nextMonth->format('Y'),
                'package_count' => 0,
                'worked_hours' => $plannedHours,
                'revenue_unit_price' => $revenueUnit,
                'revenue_total' => round($plannedHours * $revenueUnit, 2),
                'courier_unit_price' => $courierUnit,
                'courier_total' => round($plannedHours * $courierUnit, 2),
                'agency_payment' => 0,
                'net_courier_payment' => round($plannedHours * $courierUnit, 2),
                'profit' => round($plannedHours * ($revenueUnit - $courierUnit), 2),
                'status_id' => $statusIds['draft'] ?? null,
                'created_by' => $admin->id,
                'description' => self::MARKER.' · Gelecek dönem taslak saatlik hakediş',
            ]);
        }
    }

    /**
     * @param  list<Business>  $businesses
     * @param  list<Courier>  $couriers
     * @param  list<array{business_id: int, courier_id: int}>  $roster
     * @param  list<Agency>  $agencies
     */
    private function seedEarningsAndFinance(
        User $admin,
        array $businesses,
        array $couriers,
        array $roster,
        array $agencies,
    ): void {
        $statusIds = EarningStatus::query()->pluck('id', 'code');
        $periods = [
            ['month' => (int) now()->subMonths(2)->format('n'), 'year' => (int) now()->subMonths(2)->format('Y')],
            ['month' => (int) now()->subMonth()->format('n'), 'year' => (int) now()->subMonth()->format('Y')],
            ['month' => (int) now()->format('n'), 'year' => (int) now()->format('Y')],
            ['month' => (int) now()->addMonth()->format('n'), 'year' => (int) now()->addMonth()->format('Y')],
        ];
        $statusCycle = ['paid', 'paid', 'approved', 'pending_review', 'draft', 'approved', 'paid', 'draft'];

        /** @var array<int, CurrentAccount> $businessAccounts */
        $businessAccounts = [];
        /** @var array<int, CurrentAccount> $courierAccounts */
        $courierAccounts = [];
        /** @var array<int, CurrentAccount> $agencyAccounts */
        $agencyAccounts = [];

        $businessById = collect($businesses)->keyBy('id');
        $agencyById = collect($agencies)->keyBy('id');
        $courierById = collect($couriers)->keyBy('id');

        $hourlyBusinessIds = Business::query()
            ->whereIn('id', collect($businesses)->pluck('id'))
            ->whereHas('activeCommercialContract', fn ($q) => $q->where('work_type', 'hourly'))
            ->pluck('id')
            ->all();

        $financePairs = array_values(array_filter(
            $roster,
            function (array $pair) use ($businessById, $hourlyBusinessIds): bool {
                $business = $businessById->get($pair['business_id']);

                return $business !== null
                    && $business->status === 'active'
                    && ! in_array($pair['business_id'], $hourlyBusinessIds, true);
            }
        ));

        foreach ($financePairs as $index => $pair) {
            /** @var Business $business */
            $business = $businessById->get($pair['business_id']);
            /** @var Courier|null $courier */
            $courier = $courierById->get($pair['courier_id']);

            if ($courier === null) {
                continue;
            }

            // Her eşleşme için 2 dönem üret (geçmiş + güncel/gelecek karışık).
            foreach ([0, 1] as $periodOffset) {
                $period = $periods[($index + $periodOffset) % count($periods)];
                $statusCode = $statusCycle[($index + $periodOffset) % count($statusCycle)];

                // Gelecek dönem sadece taslak.
                if ($period['year'] > (int) now()->format('Y')
                    || ($period['year'] === (int) now()->format('Y') && $period['month'] > (int) now()->format('n'))) {
                    $statusCode = 'draft';
                }

                $packageCount = fake()->numberBetween(180, 900);
                $revenueUnit = 48.0;
                $courierUnit = 36.0;
                $revenueTotal = round($packageCount * $revenueUnit, 2);
                $courierTotal = round($packageCount * $courierUnit, 2);
                $agencyPayment = $courier->agency_id ? round($revenueTotal * 0.08, 2) : 0.0;
                $profit = round($revenueTotal - $courierTotal - $agencyPayment, 2);

                $earning = EarningLine::factory()->create([
                    'business_id' => $business->id,
                    'courier_id' => $courier->id,
                    'earning_type' => 'package_based',
                    'pricing_model' => 'per_package',
                    'period_month' => $period['month'],
                    'period_year' => $period['year'],
                    'package_count' => $packageCount,
                    'revenue_unit_price' => $revenueUnit,
                    'revenue_total' => $revenueTotal,
                    'courier_unit_price' => $courierUnit,
                    'courier_total' => $courierTotal,
                    'agency_payment' => $agencyPayment,
                    'net_courier_payment' => $courierTotal,
                    'profit' => $profit,
                    'status_id' => $statusIds[$statusCode] ?? $statusIds['draft'],
                    'created_by' => $admin->id,
                    'description' => self::MARKER,
                ]);

                // Taslak gelecek hakedişlerde finans hareketi üretme.
                if ($statusCode === 'draft' && (
                    $period['year'] > (int) now()->format('Y')
                    || ($period['year'] === (int) now()->format('Y') && $period['month'] > (int) now()->format('n'))
                )) {
                    continue;
                }

                $businessAccounts[$business->id] ??= CurrentAccount::factory()->business()->create([
                    'title' => $business->brand_name,
                    'accountable_type' => Business::class,
                    'accountable_id' => $business->id,
                    'tax_number' => $business->tax_number,
                    'phone' => $business->phone,
                    'email' => $business->email,
                    'status' => 'active',
                ]);

                $courierAccounts[$courier->id] ??= CurrentAccount::factory()->courier()->create([
                    'title' => $courier->full_name,
                    'accountable_type' => Courier::class,
                    'accountable_id' => $courier->id,
                    'phone' => $courier->phone,
                    'email' => $courier->email,
                    'status' => 'active',
                ]);

                $businessAccount = $businessAccounts[$business->id];
                $courierAccount = $courierAccounts[$courier->id];

                $revenueFactory = FinanceRevenue::factory()->state([
                    'business_id' => $business->id,
                    'earning_line_id' => $earning->id,
                    'current_account_id' => $businessAccount->id,
                    'amount' => $revenueTotal,
                    'period_month' => $period['month'],
                    'period_year' => $period['year'],
                    'period_label' => sprintf('%02d/%d', $period['month'], $period['year']),
                    'created_by' => $admin->id,
                    'description' => self::MARKER,
                ]);

                $revenueFactory = match ($statusCode) {
                    'paid' => $revenueFactory->collected(),
                    'pending_review' => $revenueFactory->overdue(),
                    default => $revenueFactory,
                };

                $revenue = $revenueFactory->create();

                $collectionFactory = FinanceCollection::factory()
                    ->forRevenue($revenue)
                    ->state(['created_by' => $admin->id, 'description' => self::MARKER]);

                $collectionFactory = match ($statusCode) {
                    'paid' => $collectionFactory->collected(),
                    'approved' => $collectionFactory->partial(),
                    default => $collectionFactory->overdue(),
                };
                $collectionFactory->create();

                $expenseFactory = FinanceExpense::factory()
                    ->courierEarning()
                    ->state([
                        'courier_id' => $courier->id,
                        'earning_line_id' => $earning->id,
                        'current_account_id' => $courierAccount->id,
                        'amount' => $courierTotal,
                        'created_by' => $admin->id,
                        'description' => self::MARKER,
                    ]);

                if (in_array($statusCode, ['paid', 'approved'], true)) {
                    $expenseFactory = $expenseFactory->paid();
                }
                $expenseFactory->create();

                $paymentFactory = FinancePayment::factory()
                    ->forCourier($courier)
                    ->state([
                        'earning_line_id' => $earning->id,
                        'current_account_id' => $courierAccount->id,
                        'total_amount' => $courierTotal,
                        'created_by' => $admin->id,
                        'description' => self::MARKER,
                    ]);

                $paymentFactory = match ($statusCode) {
                    'paid' => $paymentFactory->paid(),
                    'approved' => $paymentFactory->partial(),
                    default => $paymentFactory,
                };
                $paymentFactory->create();

                if ($agencyPayment > 0 && $courier->agency_id) {
                    /** @var Agency|null $agency */
                    $agency = $agencyById->get($courier->agency_id);

                    if ($agency) {
                        $agencyAccounts[$agency->id] ??= CurrentAccount::factory()->agency()->create([
                            'title' => $agency->brand_name,
                            'accountable_type' => Agency::class,
                            'accountable_id' => $agency->id,
                            'tax_number' => $agency->tax_number,
                            'status' => 'active',
                        ]);

                        $agencyAccount = $agencyAccounts[$agency->id];

                        FinanceExpense::factory()
                            ->agencyEarning()
                            ->paid()
                            ->create([
                                'agency_id' => $agency->id,
                                'earning_line_id' => $earning->id,
                                'current_account_id' => $agencyAccount->id,
                                'amount' => $agencyPayment,
                                'created_by' => $admin->id,
                                'description' => self::MARKER,
                            ]);

                        FinancePayment::factory()
                            ->forAgency($agency)
                            ->paid()
                            ->create([
                                'earning_line_id' => $earning->id,
                                'current_account_id' => $agencyAccount->id,
                                'total_amount' => $agencyPayment,
                                'created_by' => $admin->id,
                                'description' => self::MARKER,
                            ]);
                    }
                }

                $invoiceFactory = FinanceInvoice::factory()
                    ->forEarning($earning)
                    ->state([
                        'current_account_id' => $businessAccount->id,
                        'created_by' => $admin->id,
                        'description' => self::MARKER,
                    ]);

                $invoiceFactory = match ($statusCode) {
                    'paid' => $invoiceFactory->collected(),
                    'draft' => $invoiceFactory->draft(),
                    default => $invoiceFactory,
                };
                $invoiceFactory->create();

                CurrentAccountMovement::factory()->credit($revenueTotal)->create([
                    'current_account_id' => $businessAccount->id,
                    'type' => 'collection',
                    'created_by' => $admin->id,
                    'description' => self::MARKER,
                ]);

                CurrentAccountMovement::factory()->debit($courierTotal)->create([
                    'current_account_id' => $courierAccount->id,
                    'type' => 'payment',
                    'created_by' => $admin->id,
                    'description' => self::MARKER,
                ]);
            }
        }

        foreach (array_slice(array_values(array_filter(
            $businesses,
            fn (Business $b): bool => $b->status === 'active'
        )), 0, 4) as $business) {
            FinanceRevenue::factory()->create([
                'business_id' => $business->id,
                'revenue_type' => 'extra_service',
                'amount' => fake()->randomFloat(2, 1500, 8000),
                'created_by' => $admin->id,
                'description' => self::MARKER,
            ]);
        }

        foreach (['fuel', 'office', 'software', 'rent', 'advertising'] as $type) {
            FinanceExpense::factory()->create([
                'expense_type' => $type,
                'created_by' => $admin->id,
                'description' => self::MARKER,
            ]);
        }
    }

    private function slugTr(string $value): string
    {
        $map = [
            'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g',
            'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o',
            'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
        ];

        return strtolower(strtr($value, $map));
    }
}
