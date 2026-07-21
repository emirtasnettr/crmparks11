<?php

namespace App\Modules\ShiftPlanning\Models;

use App\Core\Traits\HasUuid;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessShiftAttendance extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'business_shift_id',
        'business_id',
        'commercial_contract_id',
        'courier_id',
        'work_date',
        'started_at',
        'ended_at',
        'status',
        'worked_minutes',
        'hourly_rate',
        'earnings_amount',
        'pricing_model',
        'notes',
        'start_latitude',
        'start_longitude',
        'start_accuracy_meters',
        'start_distance_meters',
        'end_latitude',
        'end_longitude',
        'end_accuracy_meters',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'worked_minutes' => 'integer',
            'hourly_rate' => 'decimal:2',
            'earnings_amount' => 'decimal:2',
            'start_latitude' => 'float',
            'start_longitude' => 'float',
            'start_accuracy_meters' => 'integer',
            'start_distance_meters' => 'integer',
            'end_latitude' => 'float',
            'end_longitude' => 'float',
            'end_accuracy_meters' => 'integer',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(BusinessShift::class, 'business_shift_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function commercialContract(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Business\Models\BusinessCommercialContract::class, 'commercial_contract_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function workedHours(): float
    {
        return round($this->worked_minutes / 60, 2);
    }
}
