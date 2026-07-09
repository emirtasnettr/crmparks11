<?php

namespace App\Modules\Finance\Models;

use App\Core\Traits\HasUuid;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Factories\FinanceRevenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceRevenue extends Model
{
    /** @use HasFactory<FinanceRevenueFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'reference',
        'business_id',
        'earning_line_id',
        'current_account_id',
        'revenue_type',
        'period_month',
        'period_year',
        'period_label',
        'invoice_no',
        'invoice_status',
        'amount',
        'vat_rate',
        'collection_status',
        'collection_date',
        'revenue_date',
        'description',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'collection_date' => 'date',
            'revenue_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): FinanceRevenueFactory
    {
        return FinanceRevenueFactory::new();
    }
}
