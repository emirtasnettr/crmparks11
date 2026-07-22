<?php

namespace App\Modules\Business\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\BusinessCommercialContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessCommercialContract extends Model
{
    /** @use HasFactory<BusinessCommercialContractFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    public const WORK_PER_PACKAGE = 'per_package';

    public const WORK_HOURLY = 'hourly';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_BIWEEKLY = 'biweekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'business_id',
        'start_date',
        'end_date',
        'work_type',
        'business_amount',
        'courier_amount',
        'net_profit',
        'guaranteed_hourly_package_fee',
        'guaranteed_package_count',
        'payment_period',
        'status',
        'supersedes_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'business_amount' => 'decimal:2',
            'courier_amount' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'guaranteed_hourly_package_fee' => 'decimal:2',
            'guaranteed_package_count' => 'integer',
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

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isHourly(): bool
    {
        return $this->work_type === self::WORK_HOURLY;
    }

    public function isPerPackage(): bool
    {
        return $this->work_type === self::WORK_PER_PACKAGE;
    }

    public function coversDate(CarbonInterface|string $date): bool
    {
        $day = \Carbon\Carbon::parse($date)->startOfDay();

        if ($this->start_date !== null && $day->lt($this->start_date->copy()->startOfDay())) {
            return false;
        }

        if ($this->end_date !== null && $day->gt($this->end_date->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * Vardiya hakedişi için kullanılacak saatlik kurye ücreti (KDV hariç).
     * Saatlik: courier_amount
     * Paket başı: null (paket adedi gerekir)
     */
    public function courierHourlyRateForAttendance(): ?float
    {
        if ($this->isHourly()) {
            return round((float) $this->courier_amount, 2);
        }

        return null;
    }

    protected static function newFactory(): BusinessCommercialContractFactory
    {
        return BusinessCommercialContractFactory::new();
    }
}
