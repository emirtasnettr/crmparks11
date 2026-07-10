<?php

namespace App\Support;

trait HasBrandDisplayName
{
    /**
     * Primary label for lists, dashboards, and selectors.
     * Brand name is preferred so branches of the same legal entity stay distinct.
     */
    public function displayName(): string
    {
        $brand = trim((string) ($this->brand_name ?? ''));

        if ($brand !== '') {
            return $brand;
        }

        return trim((string) ($this->company_name ?? '')) ?: '—';
    }
}
