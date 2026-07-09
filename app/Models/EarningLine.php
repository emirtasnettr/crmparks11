<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Database\Factories\EarningLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EarningLine extends Model
{
    /** @use HasFactory<EarningLineFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'batch_id',
        'business_id',
        'courier_id',
        'assignment_id',
        'business_pricing_id',
        'earning_type',
        'pricing_model',
        'period_month',
        'period_year',
        'package_count',
        'revenue_unit_price',
        'revenue_total',
        'courier_unit_price',
        'courier_total',
        'agency_payment',
        'extra_payment',
        'extra_expense',
        'deduction',
        'net_courier_payment',
        'profit',
        'description',
        'status_id',
        'approved_by',
        'approved_at',
        'paid_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(BusinessCourierAssignment::class, 'assignment_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(EarningStatus::class, 'status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): EarningLineFactory
    {
        return EarningLineFactory::new();
    }
}
