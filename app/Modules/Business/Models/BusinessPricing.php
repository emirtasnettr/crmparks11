<?php

namespace App\Modules\Business\Models;

use App\Core\Traits\HasUuid;
use App\Models\PricingModelType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessPricing extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'pricing_model_type_id',
        'label',
        'customer_unit_price',
        'courier_unit_price',
        'agency_unit_price',
        'custom_config',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'customer_unit_price' => 'decimal:2',
            'courier_unit_price' => 'decimal:2',
            'agency_unit_price' => 'decimal:2',
            'custom_config' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function pricingModelType(): BelongsTo
    {
        return $this->belongsTo(PricingModelType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
