<?php

namespace App\Modules\ShiftPlanning\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessShift extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'start_time',
        'end_time',
        'required_headcount',
        'start_date',
        'end_date',
        'days_of_week',
        'excluded_dates',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'required_headcount' => 'integer',
            'days_of_week' => 'array',
            'excluded_dates' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rosterCouriers(): BelongsToMany
    {
        return $this->belongsToMany(Courier::class, 'business_shift_couriers')
            ->withTimestamps()
            ->orderBy('full_name');
    }

    public function shiftCouriers(): HasMany
    {
        return $this->hasMany(BusinessShiftCourier::class);
    }

    public function jokerAssignments(): HasMany
    {
        return $this->hasMany(BusinessShiftJokerAssignment::class);
    }

    /** @deprecated day-based staffing — kept for legacy migration data */
    public function dayCouriers(): HasMany
    {
        return $this->hasMany(BusinessShiftDayCourier::class);
    }
}
