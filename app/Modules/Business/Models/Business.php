<?php

namespace App\Modules\Business\Models;

use App\Core\Traits\HasUuid;
use App\Models\City;
use App\Models\District;
use App\Models\User;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_name',
        'brand_name',
        'tax_office',
        'tax_number',
        'phone',
        'email',
        'website',
        'city_id',
        'district_id',
        'address',
        'status',
        'earning_period',
        'notes',
        'logo_path',
        'created_by',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pricings(): HasMany
    {
        return $this->hasMany(BusinessPricing::class);
    }

    public function activePricing(): HasOne
    {
        return $this->hasOne(BusinessPricing::class)
            ->where('is_active', true)
            ->latestOfMany();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BusinessCourierAssignment::class);
    }

    public function activeCourierCount(): int
    {
        return (int) $this->assignments()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now());
            })
            ->pluck('courier_id')
            ->unique()
            ->count();
    }

    protected static function newFactory(): BusinessFactory
    {
        return BusinessFactory::new();
    }
}
