<?php

namespace App\Modules\Finance\Models;

use App\Core\Traits\HasUuid;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use Database\Factories\FinancePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancePayment extends Model
{
    /** @use HasFactory<FinancePaymentFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'reference',
        'recipient_type',
        'courier_id',
        'agency_id',
        'recipient_id',
        'recipient_name',
        'earning_line_id',
        'current_account_id',
        'source',
        'scheduled_date',
        'total_amount',
        'paid_amount',
        'status',
        'is_active',
        'description',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'scheduled_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Courier, $this>
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * @return BelongsTo<EarningLine, $this>
     */
    public function earningLine(): BelongsTo
    {
        return $this->belongsTo(EarningLine::class);
    }

    /**
     * @return BelongsTo<CurrentAccount, $this>
     */
    public function currentAccount(): BelongsTo
    {
        return $this->belongsTo(CurrentAccount::class);
    }

    /**
     * @return HasMany<FinancePaymentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(FinancePaymentLine::class, 'payment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): FinancePaymentFactory
    {
        return FinancePaymentFactory::new();
    }
}
