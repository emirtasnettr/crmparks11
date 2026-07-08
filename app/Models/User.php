<?php

namespace App\Models;

use App\Core\Enums\Status;
use App\Core\Enums\Theme;
use App\Core\Enums\UserType;
use App\Core\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'phone', 'avatar_path',
    'user_type', 'profileable_type', 'profileable_id',
    'status', 'theme', 'locale', 'last_login_at', 'last_login_ip',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuid, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'status' => Status::class,
            'theme' => Theme::class,
            'last_login_at' => 'datetime',
        ];
    }

    public function profileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isInternal(): bool
    {
        return $this->user_type === UserType::Internal;
    }

    public function initials(): string
    {
        $parts = explode(' ', trim($this->name));
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: 'U';
    }
}
