<?php

namespace App\Modules\ShiftPlanning\Models;

use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessShiftDayCourier extends Model
{
    protected $table = 'business_shift_day_couriers';

    protected $fillable = [
        'business_shift_id',
        'work_date',
        'courier_id',
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

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
