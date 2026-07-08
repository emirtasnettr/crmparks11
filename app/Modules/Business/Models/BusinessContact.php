<?php

namespace App\Modules\Business\Models;

use App\Core\Traits\HasUuid;
use Database\Factories\BusinessContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessContact extends Model
{
    /** @use HasFactory<BusinessContactFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'full_name',
        'title',
        'phone',
        'email',
        'notes',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    protected static function newFactory(): BusinessContactFactory
    {
        return BusinessContactFactory::new();
    }
}
