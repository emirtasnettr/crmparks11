<?php

namespace App\Modules\Courier\Models;

use App\Core\Traits\HasUuid;
use Database\Factories\CourierVehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourierVehicle extends Model
{
    /** @use HasFactory<CourierVehicleFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'courier_id',
        'vehicle_type',
        'plate',
        'brand',
        'model',
        'model_year',
        'color',
        'license_number',
        'insurance_policy_number',
        'insurance_expiry_date',
        'status',
        'registered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'model_year' => 'integer',
            'insurance_expiry_date' => 'date',
            'registered_at' => 'date',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    protected static function newFactory(): CourierVehicleFactory
    {
        return CourierVehicleFactory::new();
    }
}
