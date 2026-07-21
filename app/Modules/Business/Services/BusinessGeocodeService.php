<?php

namespace App\Modules\Business\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusinessGeocodeService
{
    /**
     * @return array{latitude: float, longitude: float, label: string}|null
     */
    public function locate(string $city, string $district, string $neighborhood = '', string $address = ''): ?array
    {
        $city = trim($city);
        $district = trim($district);
        $neighborhood = trim($neighborhood);
        $address = trim($address);

        if ($city === '' && $address === '' && $neighborhood === '') {
            return null;
        }

        foreach ($this->candidateQueries($city, $district, $neighborhood, $address) as $query) {
            $result = $this->searchCached($query);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function candidateQueries(string $city, string $district, string $neighborhood, string $address): array
    {
        $normalizedAddress = $this->normalizeAddress($address);
        $withoutNumber = $this->stripHouseNumber($normalizedAddress);
        $streetOnly = $this->streetHint($withoutNumber);
        $neighborhoodLabel = $neighborhood !== ''
            ? (str_contains(mb_strtolower($neighborhood), 'mahalle') ? $neighborhood : $neighborhood.' Mahallesi')
            : '';

        $candidates = [];

        // Mahalle seçiliyse önce onu dene — en güvenilir sonuç.
        if ($neighborhoodLabel !== '') {
            $candidates[] = $this->joinParts([$neighborhoodLabel, $district, $city, 'Türkiye']);
        }

        if ($streetOnly !== '') {
            $candidates[] = $this->joinParts([$streetOnly, $neighborhoodLabel, $district, $city, 'Türkiye']);
        } elseif ($withoutNumber !== '') {
            $candidates[] = $this->joinParts([$withoutNumber, $neighborhoodLabel, $district, $city, 'Türkiye']);
        } elseif ($normalizedAddress !== '') {
            $candidates[] = $this->joinParts([$normalizedAddress, $neighborhoodLabel, $district, $city, 'Türkiye']);
        }

        if ($district !== '' && $city !== '') {
            $candidates[] = $this->joinParts([$district, $city, 'Türkiye']);
        }

        if ($city !== '') {
            $candidates[] = $this->joinParts([$city, 'Türkiye']);
        }

        $unique = [];
        foreach ($candidates as $candidate) {
            $key = mb_strtolower($candidate);
            if ($candidate !== '' && ! isset($unique[$key])) {
                $unique[$key] = $candidate;
            }
        }

        return array_values($unique);
    }

    /**
     * @return array{latitude: float, longitude: float, label: string}|null
     */
    private function searchCached(string $query): ?array
    {
        $cacheKey = 'business-geocode:v4:'.md5(mb_strtolower($query));

        /** @var array{latitude: float, longitude: float, label: string}|false|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['latitude'], $cached['longitude'])) {
            return $cached;
        }

        // Photon önce (datacenter IP'lerde Nominatim sık engellenir).
        $result = $this->searchPhoton($query) ?? $this->searchNominatim($query);

        if ($result !== null) {
            Cache::put($cacheKey, $result, now()->addDay());
        }

        return $result;
    }

    /**
     * @return array{latitude: float, longitude: float, label: string}|null
     */
    private function searchPhoton(string $query): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'CRMLog/1.0 (business-location)',
                    'Accept' => 'application/json',
                ])
                ->get('https://photon.komoot.io/api/', [
                    'q' => $query,
                    'limit' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Photon geocode failed', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $coords = $response->json('features.0.geometry.coordinates');
        if (! is_array($coords) || count($coords) < 2) {
            return null;
        }

        $lon = (float) $coords[0];
        $lat = (float) $coords[1];
        if ($lat < 35 || $lat > 43 || $lon < 25 || $lon > 45) {
            // Türkiye dışı sonucu ele.
            return null;
        }

        $props = $response->json('features.0.properties') ?? [];
        $labelParts = array_filter([
            $props['name'] ?? null,
            $props['street'] ?? null,
            $props['locality'] ?? null,
            $props['district'] ?? null,
            $props['city'] ?? null,
            $props['state'] ?? null,
            $props['country'] ?? null,
        ]);

        return [
            'latitude' => round($lat, 7),
            'longitude' => round($lon, 7),
            'label' => $labelParts !== [] ? implode(', ', $labelParts) : $query,
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, label: string}|null
     */
    private function searchNominatim(string $query): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'CRMLog/1.0 (business-location; contact: admin@crmlog.com)',
                    'Accept-Language' => 'tr',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'tr',
                    'addressdetails' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Nominatim geocode failed', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $first = $response->json('0');
        if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
            return null;
        }

        return [
            'latitude' => round((float) $first['lat'], 7),
            'longitude' => round((float) $first['lon'], 7),
            'label' => (string) ($first['display_name'] ?? $query),
        ];
    }

    private function normalizeAddress(string $address): string
    {
        if ($address === '') {
            return '';
        }

        $replacements = [
            '/\bMah\.?\b/iu' => 'Mahallesi',
            '/\bMh\.?\b/iu' => 'Mahallesi',
            '/\bCad\.?\b/iu' => 'Caddesi',
            '/\bCd\.?\b/iu' => 'Caddesi',
            '/\bSok\.?\b/iu' => 'Sokak',
            '/\bSk\.?\b/iu' => 'Sokak',
            '/\bBul\.?\b/iu' => 'Bulvarı',
            '/\bBlv\.?\b/iu' => 'Bulvarı',
            '/\bApt\.?\b/iu' => 'Apartmanı',
            '/\s+/' => ' ',
        ];

        $normalized = $address;
        foreach ($replacements as $pattern => $replacement) {
            $normalized = preg_replace($pattern, $replacement, $normalized) ?? $normalized;
        }

        return trim($normalized);
    }

    private function stripHouseNumber(string $address): string
    {
        if ($address === '') {
            return '';
        }

        $stripped = preg_replace(
            '/\b(No|Numara|Num\.?|N[oº°])\s*[:.]?\s*\d+[A-Za-z]?\/?\d*\b/iu',
            '',
            $address
        ) ?? $address;

        $stripped = preg_replace('/\b\d+[A-Za-z]?\/?\d*\s*$/u', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/\s+/', ' ', $stripped) ?? $stripped;

        return trim($stripped, " \t\n\r\0\x0B,.-");
    }

    private function streetHint(string $address): string
    {
        if ($address === '') {
            return '';
        }

        if (preg_match('/\b([^\d,]+?(?:Caddesi|Cadde|Sokak|Bulvarı|Bulvar))\b/iu', $address, $match) === 1) {
            return trim($match[1]);
        }

        return '';
    }

    /**
     * @param  list<string>  $parts
     */
    private function joinParts(array $parts): string
    {
        return implode(', ', array_values(array_filter(
            array_map('trim', $parts),
            fn (string $part): bool => $part !== ''
        )));
    }
}
