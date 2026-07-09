<?php

namespace App\Modules\Agency\Models;

use App\Core\Traits\HasUuid;
use Database\Factories\AgencyContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgencyContact extends Model
{
    /** @use HasFactory<AgencyContactFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'agency_id',
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

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    protected static function newFactory(): AgencyContactFactory
    {
        return AgencyContactFactory::new();
    }
}
