<?php

namespace App\Modules\Agency\Models;

use App\Core\Traits\HasUuid;
use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use App\Modules\Agency\Models\AgencyContact;
use App\Support\HasBrandDisplayName;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasBrandDisplayName, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_name',
        'brand_name',
        'tax_office',
        'tax_number',
        'mersis_number',
        'trade_registry_number',
        'phone',
        'email',
        'website',
        'city_id',
        'district_id',
        'authorized_person',
        'address',
        'commission_rate',
        'payment_period',
        'bank_key',
        'account_holder',
        'iban',
        'status',
        'notes',
        'logo_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
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

    public function contacts(): HasMany
    {
        return $this->hasMany(AgencyContact::class);
    }

    public function couriers(): HasMany
    {
        return $this->hasMany(Courier::class);
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Contract::class, 'contractable');
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Document::class, 'documentable');
    }

    public function activeCourierCount(): int
    {
        return (int) $this->couriers()
            ->where('courier_type', 'agency')
            ->where('status', 'active')
            ->count();
    }

    protected static function newFactory(): AgencyFactory
    {
        return AgencyFactory::new();
    }
}
