<?php

namespace App\Modules\ShiftPlanning\Models;

use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessShiftCourier extends Model
{
    protected $fillable = [
        'business_shift_id',
        'courier_id',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(BusinessShift::class, 'business_shift_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
