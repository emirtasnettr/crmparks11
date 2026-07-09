<?php

namespace App\Modules\Finance\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Factories\FinanceCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCollection extends Model
{
    /** @use HasFactory<FinanceCollectionFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'reference',
        'business_id',
        'revenue_id',
        'current_account_id',
        'source',
        'invoice_no',
        'due_date',
        'total_amount',
        'collected_amount',
        'status',
        'description',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'due_date' => 'date',
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
     * @return BelongsTo<FinanceRevenue, $this>
     */
    public function revenue(): BelongsTo
    {
        return $this->belongsTo(FinanceRevenue::class, 'revenue_id');
    }

    /**
     * @return BelongsTo<CurrentAccount, $this>
     */
    public function currentAccount(): BelongsTo
    {
        return $this->belongsTo(CurrentAccount::class);
    }

    /**
     * @return HasMany<FinanceCollectionPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(FinanceCollectionPayment::class, 'collection_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): FinanceCollectionFactory
    {
        return FinanceCollectionFactory::new();
    }
}
