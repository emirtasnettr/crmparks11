<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceCollectionPayment extends Model
{
    protected $fillable = [
        'collection_id',
        'amount',
        'payment_date',
        'payment_method',
        'payment_reference',
        'bank',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<FinanceCollection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(FinanceCollection::class, 'collection_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
