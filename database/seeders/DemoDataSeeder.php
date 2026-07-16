<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Contract;
use App\Models\District;
use App\Models\Document;
use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use App\Modules\Courier\Models\CourierVehicle;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Support\DemoDataGuard;
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
        ['first' => 'Merve', 'last' => 'Kara'],
        ['first' => 'Serkan', 'last' => 'Yavuz'],
    ];

    public function run(): void
    {
        DemoDataGuard::assertAllowed();

        if (! City::query()->exists()) {
            $this->call(CitySeeder::class);
        }

        if (! EarningStatus::query()->exists()) {
            $this->call(LookupTableSeeder::class);
        }

        $admin = User::query()->where('email', 'admin@crmlog.com')->first()
            ?? User::factory()->create([
                'name' => 'Süper Admin',
                'email' => 'admin@crmlog.com',
            ]);

        if (! $admin->hasRole('super_admin')) {
            $this->call(RoleAndPermissionSeeder::class);
            $admin->assignRole('super_admin');
        }

        fake()->unique(true);

        DB::transaction(function () use ($admin): void {
            $city = City::query()->where('name', 'İstanbul')->first()
                ?? City::query()->orderBy('id')->firstOrFail();
            $district = District::query()->where('city_id', $city->id)->orderBy('id')->first()
                ?? District::query()->orderBy('id')->firstOrFail();

            $agencies = $this->seedAgencies($admin, $city, $district);
            $businesses = $this->seedBusinesses($admin, $city, $district);
            $couriers = $this->seedCouriers($admin, $agencies);
            $assignments = $this->seedAssignments($admin, $businesses, $couriers);
            $this->seedEarningsAndFinance($admin, $businesses, $couriers, $assignments, $agencies);
        });

        $opening = Business::query()->where('notes', self::MARKER)->where('status', 'opening_stage')->count();
        $active = Business::query()->where('notes', self::MARKER)->where('status', 'active')->count();

        $this->command?->info("Demo veri hazır: {$active} aktif, {$opening} açılış aşamasında işletme + acente/kurye/finans.");
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
                'assign' => 4,
            ],
            [
                'company_name' => 'Pizza Locale Gıda Ltd. Şti.',
                'brand_name' => 'Pizza Locale',
                'tax_number' => '9000001002',
                'status' => 'active',
                'planned_courier_count' => 5,
                'earning_period' => 'weekly',
                'assign' => 3,
            ],
            [
                'company_name' => 'Kahve Rengi İşletmeleri A.Ş.',
                'brand_name' => 'Kahve Rengi',
                'tax_number' => '9000001003',
                'status' => 'active',
                'planned_courier_count' => 4,
                'earning_period' => 'biweekly',
                'assign' => 3,
            ],
            [
                'company_name' => 'Nori Sushi İstanbul Ltd.',
                'brand_name' => 'Nori Sushi',
                'tax_number' => '9000001004',
                'status' => 'active',
                'planned_courier_count' => 3,
                'earning_period' => 'monthly',
                'assign' => 2,
            ],
            [
                'company_name' => 'Döneristan Gıda A.Ş.',
                'brand_name' => 'Döneristan',
                'tax_number' => '9000001005',
                'status' => 'active',
                'planned_courier_count' => 8,
                'earning_period' => 'weekly',
                'assign' => 5,
            ],
            [
                'company_name' => 'Yeşil Bowl Healthy Food Ltd.',
                'brand_name' => 'Yeşil Bowl',
                'tax_number' => '9000001006',
                'status' => 'active',
                'planned_courier_count' => 4,
                'earning_period' => 'weekly',
                'assign' => 2,
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
                'assign' => 2,
            ],
            [
                'company_name' => 'Anne Sofrası Yemekleri A.Ş.',
                'brand_name' => 'Anne Sofrası',
                'tax_number' => '9000001008',
                'status' => 'opening_stage',
                'start_date' => now()->addDays(4)->toDateString(),
                'planned_courier_count' => 8,
                'earning_period' => 'weekly',
                'assign' => 4,
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
                'assign' => 4,
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
                $n === 19 => 'on_leave',
                $n === 20 => 'inactive',
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
     * @param  list<Business>  $businesses
     * @param  list<Courier>  $couriers
     * @return list<BusinessCourierAssignment>
     */
    private function seedAssignments(User $admin, array $businesses, array $couriers): array
    {
        $activeCouriers = array_values(array_filter(
            $couriers,
            fn (Courier $courier): bool => $courier->status === 'active'
        ));

        $assignments = [];
        $cursor = 0;

        foreach ($businesses as $business) {
            $need = (int) $business->getAttribute('_demo_assign_count');
            if ($need <= 0) {
                continue;
            }

            for ($i = 0; $i < $need; $i++) {
                $courier = $activeCouriers[$cursor % count($activeCouriers)];
                $cursor++;

                $assignments[] = BusinessCourierAssignment::factory()->create([
                    'business_id' => $business->id,
                    'courier_id' => $courier->id,
                    'assigned_by' => $admin->id,
                    'status' => 'active',
                    'start_date' => now()->subMonths(2)->toDateString(),
                    'end_date' => null,
                    'notes' => self::MARKER,
                ]);
            }
        }

        // Bir sonlandırılmış atama (geçmiş) — aktif işletmede
        $firstActive = collect($businesses)->firstWhere('status', 'active');
        $spareCourier = $activeCouriers[count($activeCouriers) - 1] ?? null;
        if ($firstActive && $spareCourier) {
            $assignments[] = BusinessCourierAssignment::factory()->create([
                'business_id' => $firstActive->id,
                'courier_id' => $spareCourier->id,
                'assigned_by' => $admin->id,
                'status' => 'inactive',
                'start_date' => now()->subMonths(6)->toDateString(),
                'end_date' => now()->subMonth()->toDateString(),
                'notes' => self::MARKER,
            ]);
        }

        return $assignments;
    }

    /**
     * @param  list<Business>  $businesses
     * @param  list<Courier>  $couriers
     * @param  list<BusinessCourierAssignment>  $assignments
     * @param  list<Agency>  $agencies
     */
    private function seedEarningsAndFinance(
        User $admin,
        array $businesses,
        array $couriers,
        array $assignments,
        array $agencies,
    ): void {
        $statusIds = EarningStatus::query()->pluck('id', 'code');
        $periods = [
            ['month' => (int) now()->subMonth()->format('n'), 'year' => (int) now()->subMonth()->format('Y')],
            ['month' => (int) now()->format('n'), 'year' => (int) now()->format('Y')],
        ];
        $statusCycle = ['paid', 'approved', 'pending_review', 'draft', 'approved', 'paid'];

        /** @var array<int, CurrentAccount> $businessAccounts */
        $businessAccounts = [];
        /** @var array<int, CurrentAccount> $courierAccounts */
        $courierAccounts = [];
        /** @var array<int, CurrentAccount> $agencyAccounts */
        $agencyAccounts = [];

        $businessById = collect($businesses)->keyBy('id');
        $agencyById = collect($agencies)->keyBy('id');
        $courierById = collect($couriers)->keyBy('id');

        $financeAssignments = array_values(array_filter(
            $assignments,
            function (BusinessCourierAssignment $assignment) use ($businessById): bool {
                $business = $businessById->get($assignment->business_id);

                return $business !== null
                    && $business->status === 'active'
                    && $assignment->status === 'active';
            }
        ));

        foreach ($financeAssignments as $index => $assignment) {
            /** @var Business $business */
            $business = $businessById->get($assignment->business_id);
            /** @var Courier|null $courier */
            $courier = $courierById->get($assignment->courier_id);

            if ($courier === null) {
                continue;
            }

            $period = $periods[$index % 2];
            $statusCode = $statusCycle[$index % count($statusCycle)];
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
                'assignment_id' => $assignment->id,
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
