<?php

namespace App\Modules\ShiftPlanning\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'days_of_week' => 'array',
            'excluded_dates' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function excludedDateList(): array
    {
        return collect($this->excluded_dates ?? [])
            ->filter()
            ->map(fn ($date) => Carbon::parse((string) $date)->toDateString())
            ->unique()
            ->values()
            ->all();
    }

    public function isDateExcluded(Carbon|string $date): bool
    {
        return in_array(Carbon::parse($date)->toDateString(), $this->excludedDateList(), true);
    }

    /**
     * @return array<int, int>
     */
    public function activeWeekDays(): array
    {
        $days = collect($this->days_of_week ?? [])
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $days !== [] ? $days : ShiftPlanningFormData::defaultDays();
    }

    public function runsOnDate(Carbon|string $date): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($this->isDateExcluded($day)) {
            return false;
        }

        if ($this->start_date && $day->lt($this->start_date->copy()->startOfDay())) {
            return false;
        }

        if ($this->end_date && $day->gt($this->end_date->copy()->startOfDay())) {
            return false;
        }

        return in_array((int) $day->dayOfWeekIso, $this->activeWeekDays(), true);
    }

    /**
     * @return array<int, string>
     */
    public function occurrenceDates(): array
    {
        if ($this->start_date === null || $this->end_date === null) {
            return [];
        }

        $days = $this->activeWeekDays();
        $excluded = $this->excludedDateList();
        $dates = [];

        foreach (CarbonPeriod::create($this->start_date, $this->end_date) as $date) {
            $key = $date->toDateString();
            if (in_array($key, $excluded, true)) {
                continue;
            }

            if (in_array((int) $date->dayOfWeekIso, $days, true)) {
                $dates[] = $key;
            }
        }

        return $dates;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dayCouriers(): HasMany
    {
        return $this->hasMany(BusinessShiftDayCourier::class);
    }

    public function couriersForDate(string $date)
    {
        return Courier::query()
            ->whereIn('id', $this->dayCouriers()->whereDate('work_date', $date)->pluck('courier_id'))
            ->orderBy('full_name')
            ->get();
    }
}
