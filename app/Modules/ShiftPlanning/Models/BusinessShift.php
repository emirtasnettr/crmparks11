<?php

namespace App\Modules\ShiftPlanning\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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

    /** @deprecated day-based staffing — kept for legacy migration data */
    public function dayCouriers(): HasMany
    {
        return $this->hasMany(BusinessShiftDayCourier::class);
    }

    public function timeRangeLabel(): string
    {
        $start = substr((string) $this->start_time, 0, 5) ?: '—';
        $end = substr((string) $this->end_time, 0, 5) ?: '—';

        return "{$start}–{$end}";
    }

    public function runsOn(CarbonInterface|string $day): bool
    {
        // Açık uçlu planlama yok: her iki tarih de zorunlu.
        if ($this->start_date === null || $this->end_date === null) {
            return false;
        }

        $date = Carbon::parse($day)->startOfDay();
        $start = $this->start_date->copy()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();

        if ($date->lt($start) || $date->gt($end)) {
            return false;
        }

        $excluded = collect($this->excluded_dates ?? [])
            ->map(fn ($value) => Carbon::parse((string) $value)->toDateString())
            ->all();

        if (in_array($date->toDateString(), $excluded, true)) {
            return false;
        }

        $daysOfWeek = $this->days_of_week;

        if (is_array($daysOfWeek) && $daysOfWeek !== []) {
            return in_array((int) $date->dayOfWeek, array_map('intval', $daysOfWeek), true);
        }

        return true;
    }
}
