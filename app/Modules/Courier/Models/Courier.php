<?php

namespace App\Modules\Courier\Models;

use App\Core\Traits\HasUuid;
use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Agency\Models\Agency;
use Database\Factories\CourierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Courier extends Model
{
    /** @use HasFactory<CourierFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'full_name',
        'phone',
        'email',
        'tc_number',
        'birth_date',
        'tax_office',
        'tax_number',
        'company_name',
        'iban',
        'bank_name',
        'account_holder',
        'vehicle_type_id',
        'plate',
        'vehicle_brand',
        'vehicle_model',
        'courier_type',
        'agency_id',
        'city_id',
        'district_id',
        'address',
        'start_date',
        'status',
        'notes',
        'photo_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'start_date' => 'date',
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

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): CourierFactory
    {
        return CourierFactory::new();
    }
}
