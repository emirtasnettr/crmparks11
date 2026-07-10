<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

final class EntityCardRedirect
{
    /**
     * Prefer returning to the entity card (with tab) when the action was started there;
     * otherwise fall back to the related index URL.
     */
    public static function after(
        string $fallbackUrl,
        string $successMessage,
        ?string $showUrl = null,
        ?string $tab = null,
    ): RedirectResponse {
        if ($showUrl !== null && self::cameFromShow($showUrl)) {
            $target = $tab !== null ? self::withTab($showUrl, $tab) : $showUrl;

            return redirect()
                ->to($target)
                ->with('success', $successMessage);
        }

        return redirect()
            ->to($fallbackUrl)
            ->with('success', $successMessage);
    }

    public static function toShow(string $showUrl, string $tab, string $successMessage): RedirectResponse
    {
        return redirect()
            ->to(self::withTab($showUrl, $tab))
            ->with('success', $successMessage);
    }

    private static function cameFromShow(string $showUrl): bool
    {
        $previousPath = parse_url(url()->previous(), PHP_URL_PATH);
        $showPath = parse_url($showUrl, PHP_URL_PATH);

        if (! is_string($previousPath) || ! is_string($showPath)) {
            return false;
        }

        return rtrim($previousPath, '/') === rtrim($showPath, '/');
    }

    private static function withTab(string $url, string $tab): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'tab='.urlencode($tab);
    }
}
