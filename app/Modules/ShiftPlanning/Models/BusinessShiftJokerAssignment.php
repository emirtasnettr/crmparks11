<?php

namespace App\Modules\ShiftPlanning\Models;

use App\Models\User;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessShiftJokerAssignment extends Model
{
    protected $fillable = [
        'business_shift_id',
        'work_date',
        'absent_courier_id',
        'joker_courier_id',
        'reason',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(BusinessShift::class, 'business_shift_id');
    }

    public function absentCourier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'absent_courier_id');
    }

    public function jokerCourier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'joker_courier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
