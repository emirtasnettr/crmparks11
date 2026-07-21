<?php

namespace App\Modules\Courier\Services;

use App\Core\Enums\Status;
use App\Core\Enums\UserType;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class CourierUserProvisioner
{
    public const DEFAULT_PASSWORD = '12345678';

    public function ensureForCourier(Courier $courier): User
    {
        if ($courier->user_id !== null) {
            $existing = User::query()->find($courier->user_id);

            if ($existing !== null) {
                return $this->syncProfile($existing, $courier);
            }
        }

        $linked = User::query()
            ->where('profileable_type', Courier::class)
            ->where('profileable_id', $courier->id)
            ->first();

        if ($linked !== null) {
            $courier->update(['user_id' => $linked->id]);

            return $this->syncProfile($linked, $courier);
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

        $this->assignCourierRole($user);

        $courier->update(['user_id' => $user->id]);

        return $user;
    }

    public function syncFromCourier(Courier $courier): User
    {
        return $this->ensureForCourier($courier);
    }

    public function updatePassword(Courier $courier, string $password): User
    {
        $user = $this->ensureForCourier($courier);
        $user->update(['password' => $password]);

        return $user->fresh();
    }

    private function resolveEmail(Courier $courier): string
    {
        $email = trim((string) $courier->email);

        if ($email === '') {
            throw new InvalidArgumentException('Kurye e-posta adresi zorunludur.');
        }

        return $email;
    }

    private function syncProfile(User $user, Courier $courier): User
    {
        $user->update([
            'name' => $courier->full_name,
            'email' => $this->resolveEmail($courier),
            'phone' => $this->resolvePhone($courier, $user),
            'status' => $this->mapStatus($courier),
        ]);

        $this->assignCourierRole($user);

        return $user->fresh();
    }

    private function assignCourierRole(User $user): void
    {
        Role::findOrCreate('courier', 'web');

        if (! $user->hasRole('courier')) {
            $user->assignRole('courier');
        }
    }

    private function resolvePhone(Courier $courier, ?User $ignoreUser = null): ?string
    {
        $phone = trim((string) $courier->phone);

        if ($phone === '') {
            return null;
        }

        $taken = User::query()
            ->where('phone', $phone)
            ->when(
                $ignoreUser?->id !== null,
                fn ($query) => $query->whereKeyNot($ignoreUser->id),
            )
            ->exists();

        if ($taken) {
            return null;
        }

        return $phone;
    }

    private function mapStatus(Courier $courier): Status
    {
        return $courier->status === 'active' ? Status::Active : Status::Inactive;
    }

}
