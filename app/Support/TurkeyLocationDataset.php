<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TurkeyLocationDataset
{
    public const DATASET_VERSION = '2025';

    public const SOURCE_URL = 'https://api.turkiyeapi.dev/v2/datasets/2025';

    public function neighborhoodsPath(): string
    {
        return database_path('data/turkey_neighborhoods_by_district.json');
    }

    public function citiesDistrictsPath(): string
    {
        return database_path('data/turkey_cities_districts.json');
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    public function neighborhoodsByDistrict(): array
    {
        $path = $this->neighborhoodsPath();

        if (! is_file($path)) {
            throw new RuntimeException("Neighborhood dataset missing: {$path}");
        }

        /** @var array<string, array<string, list<string>>> $data */
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @return list<string>
     */
    public function neighborhoodsFor(string $city, string $district): array
    {
        $tree = $this->neighborhoodsByDistrict();

        return array_values($tree[$city][$district] ?? []);
    }

    /**
     * TurkiyeAPI 2025 verisini indirip yerel JSON dosyalarını günceller.
     *
     * @return array{cities: int, districts: int, neighborhoods: int, version: string}
     */
    public function refreshFromRemote(): array
    {
        $provinces = $this->fetchJson(self::SOURCE_URL.'/provinces.json');
        $districts = $this->fetchJson(self::SOURCE_URL.'/districts.json');
        $neighborhoods = $this->fetchJson(self::SOURCE_URL.'/neighborhoods.json');

        $provinceNames = [];
        $citiesDistricts = [];

        foreach ($provinces as $province) {
            $id = (int) $province['id'];
            $name = (string) $province['name'];
            $provinceNames[$id] = $name;
            $citiesDistricts[] = [
                'name' => $name,
                'plate_code' => $id,
                'districts' => [],
            ];
        }

        $citiesByPlate = [];
        foreach ($citiesDistricts as $index => $city) {
            $citiesByPlate[(int) $city['plate_code']] = $index;
        }

        $districtMeta = [];
        foreach ($districts as $district) {
            $provinceId = (int) $district['provinceId'];
            $districtId = (int) $district['id'];
            $districtName = (string) $district['name'];
            $districtMeta[$districtId] = [
                'name' => $districtName,
                'provinceId' => $provinceId,
            ];

            if (! isset($citiesByPlate[$provinceId])) {
                continue;
            }

            $citiesDistricts[$citiesByPlate[$provinceId]]['districts'][] = $districtName;
        }

        foreach ($citiesDistricts as &$city) {
            $city['districts'] = array_values(array_unique($city['districts']));
            sort($city['districts'], SORT_LOCALE_STRING);
        }
        unset($city);

        usort(
            $citiesDistricts,
            fn (array $a, array $b): int => ((int) $a['plate_code']) <=> ((int) $b['plate_code'])
        );

        $tree = [];
        foreach ($neighborhoods as $row) {
            $districtId = (int) $row['districtId'];
            if (! isset($districtMeta[$districtId])) {
                continue;
            }

            $cityName = $provinceNames[$districtMeta[$districtId]['provinceId']] ?? null;
            $districtName = $districtMeta[$districtId]['name'];
            if ($cityName === null) {
                continue;
            }

            $tree[$cityName][$districtName][] = (string) $row['name'];
        }

        foreach ($tree as &$districtMap) {
            foreach ($districtMap as &$names) {
                $names = array_values(array_unique($names));
                sort($names, SORT_LOCALE_STRING);
            }
            unset($names);
            ksort($districtMap, SORT_LOCALE_STRING);
        }
        unset($districtMap);
        ksort($tree, SORT_LOCALE_STRING);

        $this->writeJson($this->citiesDistrictsPath(), $citiesDistricts);
        $this->writeJson($this->neighborhoodsPath(), $tree);

        return [
            'cities' => count($citiesDistricts),
            'districts' => array_sum(array_map(
                fn (array $city): int => count($city['districts']),
                $citiesDistricts
            )),
            'neighborhoods' => array_sum(array_map(
                fn (array $districts): int => array_sum(array_map('count', $districts)),
                $tree
            )),
            'version' => self::DATASET_VERSION,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchJson(string $url): array
    {
        $response = Http::timeout(120)
            ->withHeaders([
                'User-Agent' => 'CRMLog/1.0 (turkey-locations)',
                'Accept' => 'application/json',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("TurkiyeAPI isteği başarısız: {$url} ({$response->status()})");
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException("TurkiyeAPI geçersiz JSON: {$url}");
        }

        /** @var list<array<string, mixed>> $payload */
        return $payload;
    }

    private function writeJson(string $path, mixed $data): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $path,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
        );
    }
}
