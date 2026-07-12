<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/turkey_cities_districts.json');

        if (! is_file($path)) {
            throw new RuntimeException("City dataset missing: {$path}");
        }

        /** @var array<int, array{name: string, plate_code: int, districts: array<int, string>}> $cities */
        $cities = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if ($cities === []) {
            throw new RuntimeException('City dataset is empty.');
        }

        $now = now();

        DB::transaction(function () use ($cities, $now): void {
            foreach ($cities as $cityData) {
                DB::table('cities')->updateOrInsert(
                    ['plate_code' => $cityData['plate_code']],
                    [
                        'name' => $cityData['name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $cityId = (int) DB::table('cities')
                    ->where('plate_code', $cityData['plate_code'])
                    ->value('id');

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
        });
    }
}
