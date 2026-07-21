<?php

namespace Database\Seeders;

use App\Support\TurkeyLocationDataset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NeighborhoodSeeder extends Seeder
{
    public function run(): void
    {
        $dataset = app(TurkeyLocationDataset::class);
        $tree = $dataset->neighborhoodsByDistrict();

        if ($tree === []) {
            throw new RuntimeException('Neighborhood dataset is empty.');
        }

        $now = now();
        $cities = DB::table('cities')->pluck('id', 'name');

        DB::transaction(function () use ($tree, $cities, $now): void {
            foreach ($tree as $cityName => $districts) {
                $cityId = $cities[$cityName] ?? null;
                if ($cityId === null) {
                    continue;
                }

                $districtIds = DB::table('districts')
                    ->where('city_id', $cityId)
                    ->pluck('id', 'name');

                foreach ($districts as $districtName => $neighborhoods) {
                    $districtId = $districtIds[$districtName] ?? null;
                    if ($districtId === null) {
                        continue;
                    }

                    foreach ($neighborhoods as $neighborhoodName) {
                        DB::table('neighborhoods')->updateOrInsert(
                            [
                                'district_id' => $districtId,
                                'name' => $neighborhoodName,
                            ],
                            [
                                'created_at' => $now,
                                'updated_at' => $now,
                            ],
                        );
                    }
                }
            }
        });
    }
}
