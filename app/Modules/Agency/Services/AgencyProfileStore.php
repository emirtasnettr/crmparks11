<?php

namespace App\Modules\Agency\Services;

class AgencyProfileStore
{
    private static function path(int $id): string
    {
        return storage_path('app/agency-profiles/'.$id.'.json');
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(int $id): array
    {
        $path = self::path($id);

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function put(int $id, array $data): void
    {
        $directory = dirname(self::path($id));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $merged = array_merge(self::get($id), $data, [
            'updated_at' => now()->toIso8601String(),
        ]);

        file_put_contents(
            self::path($id),
            json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
