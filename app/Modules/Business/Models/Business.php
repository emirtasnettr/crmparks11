<?php

namespace App\Modules\Business\Models;

use App\Core\Traits\HasUuid;
use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Support\HasBrandDisplayName;
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
    use HasBrandDisplayName, HasFactory, HasUuid, SoftDeletes;

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
        'contract_end_date',
        'estimated_opening_date',
        'start_date',
        'earning_period',
        'first_invoice_date',
        'planned_courier_count',
        'guaranteed_package_count',
        'notes',
        'logo_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_end_date' => 'date',
            'estimated_opening_date' => 'date',
            'start_date' => 'date',
            'first_invoice_date' => 'date',
            'planned_courier_count' => 'integer',
            'guaranteed_package_count' => 'decimal:2',
        ];
    }

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

    public function shifts(): HasMany
    {
        return $this->hasMany(BusinessShift::class);
    }

    public function commercialContracts(): HasMany
    {
        return $this->hasMany(BusinessCommercialContract::class);
    }

    public function activeCommercialContract(): HasOne
    {
        return $this->hasOne(BusinessCommercialContract::class)
            ->where('status', BusinessCommercialContract::STATUS_ACTIVE)
            ->latestOfMany('start_date');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(BusinessContact::class);
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Contract::class, 'contractable');
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Document::class, 'documentable');
    }

    /**
     * Aktif vardiya kadrolarındaki benzersiz kurye sayısı.
     */
    public function activeCourierCount(): int
    {
        return (int) BusinessShiftCourier::query()
            ->whereHas('shift', function ($query): void {
                $query->where('business_id', $this->id)
                    ->where('is_active', true)
                    ->where(function ($inner): void {
                        $inner->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', now()->toDateString());
                    });
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
