<?php

namespace App\Support\Installer;

use InvalidArgumentException;
use RuntimeException;

class EnvironmentConfigurator
{
    private string $content;

    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? base_path('.env');
        $envPath = $this->path;

        if (! is_file($envPath)) {
            $examplePath = base_path('.env.example');

            if (! is_file($examplePath)) {
                throw new RuntimeException('.env.example dosyası bulunamadı.');
            }

            if (! copy($examplePath, $envPath)) {
                throw new RuntimeException('.env dosyası oluşturulamadı.');
            }
        }

        $content = file_get_contents($envPath);

        if ($content === false) {
            throw new RuntimeException('.env dosyası okunamadı.');
        }

        $this->content = $content;
    }

    public function set(string $key, string $value): self
    {
        $line = $key.'='.$this->escapeValue($value);
        $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $this->content)) {
            $this->content = preg_replace($pattern, $line, $this->content) ?? $this->content;
        } else {
            $this->content = rtrim($this->content).PHP_EOL.$line.PHP_EOL;
        }

        return $this;
    }

    public function save(?string $path = null): void
    {
        $envPath = $path ?? $this->path;

        if (file_put_contents($envPath, $this->content, LOCK_EX) === false) {
            throw new RuntimeException('.env dosyası kaydedilemedi.');
        }

        @chmod($envPath, 0600);
    }

    public static function validateAppUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Geçerli bir APP_URL girin (ör. https://alanadiniz.com).');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('APP_URL yalnızca http veya https olabilir.');
        }

        return rtrim($url, '/');
    }

    private function escapeValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        if (preg_match('/[\s#"\']/', $value) === 1) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}
