<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class RoleProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'role_name',
        'display_name',
        'description',
        'status',
        'is_system',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
