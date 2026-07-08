<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'İstanbul', 'plate_code' => 34, 'districts' => ['Kadıköy', 'Beşiktaş', 'Şişli', 'Ümraniye', 'Ataşehir', 'Bakırköy', 'Fatih', 'Maltepe']],
            ['name' => 'Ankara', 'plate_code' => 6, 'districts' => ['Çankaya', 'Keçiören', 'Yenimahalle', 'Mamak', 'Etimesgut']],
            ['name' => 'İzmir', 'plate_code' => 35, 'districts' => ['Konak', 'Karşıyaka', 'Bornova', 'Buca', 'Bayraklı']],
            ['name' => 'Bursa', 'plate_code' => 16, 'districts' => ['Osmangazi', 'Nilüfer', 'Yıldırım', 'Gemlik']],
            ['name' => 'Antalya', 'plate_code' => 7, 'districts' => ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Alanya']],
            ['name' => 'Adana', 'plate_code' => 1, 'districts' => ['Seyhan', 'Çukurova', 'Yüreğir', 'Sarıçam']],
            ['name' => 'Konya', 'plate_code' => 42, 'districts' => ['Selçuklu', 'Meram', 'Karatay']],
            ['name' => 'Gaziantep', 'plate_code' => 27, 'districts' => ['Şahinbey', 'Şehitkamil', 'Oğuzeli']],
        ];

        $now = now();

        foreach ($cities as $cityData) {
            DB::table('cities')->updateOrInsert(
                ['plate_code' => $cityData['plate_code']],
                [
                    'name' => $cityData['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $cityId = DB::table('cities')->where('plate_code', $cityData['plate_code'])->value('id');

            foreach ($cityData['districts'] as $district) {
                DB::table('districts')->updateOrInsert(
                    ['city_id' => $cityId, 'name' => $district],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }
}
