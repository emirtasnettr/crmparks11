<?php

namespace App\Support\Installer;

class PrerequisiteChecker
{
    private const MIN_PHP_VERSION = '8.3.0';

    /** @var list<string> */
    private const REQUIRED_EXTENSIONS = [
        'bcmath',
        'ctype',
        'curl',
        'fileinfo',
        'intl',
        'mbstring',
        'openssl',
        'pdo',
        'tokenizer',
        'xml',
        'zip',
    ];

    /**
     * @return list<string>
     */
    public function failures(bool $requireFrontendBuild = true): array
    {
        $failures = [];

        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            $failures[] = 'PHP '.self::MIN_PHP_VERSION.' veya üzeri gerekli (mevcut: '.PHP_VERSION.').';
        }

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            if (! extension_loaded($extension)) {
                $failures[] = "PHP eklentisi eksik: {$extension}";
            }
        }

        foreach ([
            storage_path() => 'storage',
            storage_path('framework') => 'storage/framework',
            storage_path('logs') => 'storage/logs',
            base_path('bootstrap/cache') => 'bootstrap/cache',
        ] as $path => $label) {
            if (! is_dir($path)) {
                $failures[] = "Dizin bulunamadı: {$label}";

                continue;
            }

            if (! is_writable($path)) {
                $failures[] = "Dizin yazılabilir değil: {$label}";
            }
        }

        if (! is_file(base_path('.env.example'))) {
            $failures[] = '.env.example dosyası bulunamadı.';
        }

        if ($requireFrontendBuild && ! is_file(public_path('build/manifest.json'))) {
            $failures[] = 'Frontend derlemesi eksik. Önce `npm ci && npm run build` çalıştırın veya --skip-build kullanın.';
        }

        return $failures;
    }
}
