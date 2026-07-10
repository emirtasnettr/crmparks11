<?php

namespace App\Support;

use RuntimeException;

/**
 * Demo / örnek veri yalnızca local ve testing ortamlarında çalışabilir.
 * Production (canlı) dahil diğer ortamlarda her zaman engellenir.
 */
final class DemoDataGuard
{
    /**
     * @return list<string>
     */
    public static function allowedEnvironments(): array
    {
        return ['local', 'testing'];
    }

    public static function isAllowed(): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        return app()->environment(self::allowedEnvironments());
    }

    public static function assertAllowed(): void
    {
        if (self::isAllowed()) {
            return;
        }

        $env = (string) app()->environment();

        throw new RuntimeException(
            "Örnek (demo) veri yalnızca local/testing ortamında yüklenebilir. "
            ."Mevcut ortam: [{$env}]. Canlıya geçirilmemelidir."
        );
    }
}
