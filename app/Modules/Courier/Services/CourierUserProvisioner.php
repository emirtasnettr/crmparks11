<?php

namespace App\Modules\Courier\Services;

use App\Core\Enums\Status;
use App\Core\Enums\UserType;
use App\Models\User;
use App\Modules\Courier\Models\Courier;

class CourierUserProvisioner
{
    public const DEFAULT_PASSWORD = '12345678';

    public function ensureForCourier(Courier $courier): User
    {
        if ($courier->user_id !== null) {
            $existing = User::query()->find($courier->user_id);

            if ($existing !== null) {
                return $existing;
            }
        }

        $linked = User::query()
            ->where('profileable_type', Courier::class)
            ->where('profileable_id', $courier->id)
            ->first();

        if ($linked !== null) {
            $courier->update(['user_id' => $linked->id]);

            return $linked;
        }

        $user = User::query()->create([
            'name' => $courier->full_name,
            'email' => $this->resolveEmail($courier),
            'phone' => $this->resolvePhone($courier),
            'password' => self::DEFAULT_PASSWORD,
            'user_type' => UserType::Courier,
            'profileable_type' => Courier::class,
            'profileable_id' => $courier->id,
            'status' => $this->mapStatus($courier),
        ]);

        $user->assignRole('courier');

        $courier->update(['user_id' => $user->id]);

        return $user;
    }

    private function resolveEmail(Courier $courier): string
    {
        $email = trim((string) $courier->email);

        if ($email !== '' && ! User::query()->where('email', $email)->exists()) {
            return $email;
        }

        $domain = $this->emailDomain();
        $candidate = sprintf('kurye.%d@%s', $courier->id, $domain);
        $suffix = 1;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = sprintf('kurye.%d.%d@%s', $courier->id, $suffix, $domain);
            $suffix++;
        }

        return $candidate;
    }

    private function resolvePhone(Courier $courier): ?string
    {
        $phone = trim((string) $courier->phone);

        if ($phone === '') {
            return null;
        }

        if (User::query()->where('phone', $phone)->exists()) {
            return null;
        }

        return $phone;
    }

    private function mapStatus(Courier $courier): Status
    {
        return $courier->status === 'active' ? Status::Active : Status::Inactive;
    }

    private function emailDomain(): string
    {
        $companyEmail = (string) config('crmlog.company.email', 'crmlog.com');
        $parts = explode('@', $companyEmail);

        return $parts[1] ?? 'crmlog.com';
    }
}
