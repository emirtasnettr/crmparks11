<?php

namespace App\Support\Installer;

use RuntimeException;

class InstallLock
{
    private const LOCK_FILE = 'framework/crmlog.installed';

    public static function path(): string
    {
        return storage_path(self::LOCK_FILE);
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function write(array $metadata): void
    {
        $directory = dirname(self::path());

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Kurulum kilidi dizini oluşturulamadı.');
        }

        $payload = json_encode([
            'installed_at' => now()->toIso8601String(),
            ...$metadata,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        if (file_put_contents(self::path(), $payload.PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Kurulum kilidi yazılamadı.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function read(): ?array
    {
        if (! self::exists()) {
            return null;
        }

        $contents = file_get_contents(self::path());

        if ($contents === false || trim($contents) === '') {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function remove(): void
    {
        if (self::exists()) {
            unlink(self::path());
        }
    }
}
