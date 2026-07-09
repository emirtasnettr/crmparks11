<?php

namespace App\Modules\Finance\Models;

use App\Core\Traits\HasUuid;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use Database\Factories\FinanceExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceExpense extends Model
{
    /** @use HasFactory<FinanceExpenseFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'reference',
        'expense_type',
        'source',
        'courier_id',
        'agency_id',
        'earning_line_id',
        'current_account_id',
        'amount',
        'vat_rate',
        'expense_date',
        'payment_status',
        'payment_date',
        'document_no',
        'description',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'payment_date' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): FinanceExpenseFactory
    {
        return FinanceExpenseFactory::new();
    }
}
