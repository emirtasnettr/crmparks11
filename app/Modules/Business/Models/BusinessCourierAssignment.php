<?php

namespace App\Modules\Business\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessCourierAssignment extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'courier_id',
        'start_date',
        'end_date',
        'status',
        'notes',
        'assigned_by',
        'ended_by',
        'ended_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
