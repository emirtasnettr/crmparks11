<?php

namespace App\Support;

use InvalidArgumentException;

class AdminInitialPasswordPolicy
{
    private const WEAK_PASSWORDS = [
        'password',
        '12345678',
        'admin123456',
        'password1234',
        'qwertyuiop12',
    ];

    public static function validate(string $password): void
    {
        if (strlen($password) < 12) {
            throw new InvalidArgumentException('Admin şifresi en az 12 karakter olmalıdır.');
        }

        if (in_array($password, self::WEAK_PASSWORDS, true)) {
            throw new InvalidArgumentException('Admin şifresi zayıf veya yaygın bir değer olamaz.');
        }
    }

    public static function isValid(string $password): bool
    {
        try {
            self::validate($password);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
