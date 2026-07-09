<?php

namespace App\Modules\Courier\Services;

use App\Modules\Courier\Data\CourierBankAccountFormData;
use App\Modules\Courier\Models\CourierBankAccount;

class CourierBankAccountPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(CourierBankAccount $account): array
    {
        return $this->enrich($account);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(CourierBankAccount $account): array
    {
        return $this->enrich($account);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(CourierBankAccount $account): array
    {
        $account->loadMissing('courier');
        $courier = $account->courier;

        return [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'courier_id' => $account->courier_id,
            'courier_name' => $courier?->full_name ?? '—',
            'courier_phone' => $courier?->phone ?? '—',
            'courier_type' => $courier?->courier_type ?? 'independent',
            'bank_key' => $account->bank_key,
            'bank_name' => CourierBankAccountFormData::banks()[$account->bank_key] ?? '—',
            'account_holder' => $account->account_holder,
            'iban' => $account->iban,
            'iban_masked' => self::maskIban($account->iban),
            'iban_formatted' => self::formatIban($account->iban),
            'branch_code' => $account->branch_code,
            'account_number' => $account->account_number,
            'is_default' => $account->is_default,
            'status' => $account->status,
            'status_label' => CourierBankAccountFormData::statuses()[$account->status] ?? '—',
            'notes' => $account->notes,
        ];
    }

    public static function maskIban(string $iban): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', $iban) ?? $iban);

        if (strlen($clean) < 8) {
            return $clean;
        }

        $first = substr($clean, 0, 4);
        $last = substr($clean, -4);

        return $first.' **** **** **** **** '.$last;
    }

    public static function formatIban(string $iban): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', $iban) ?? $iban);

        return trim(chunk_split($clean, 4, ' '));
    }
}
