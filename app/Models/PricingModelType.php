<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingModelType extends Model
{
    protected $fillable = [
        'code',
        'label',
        'requires_package_count',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_package_count' => 'boolean',
        ];
    }
}
