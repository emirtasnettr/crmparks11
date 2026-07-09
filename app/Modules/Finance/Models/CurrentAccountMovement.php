<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Database\Factories\CurrentAccountMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrentAccountMovement extends Model
{
    /** @use HasFactory<CurrentAccountMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'current_account_id',
        'transaction_date',
        'document_no',
        'type',
        'debit',
        'credit',
        'description',
        'related_type',
        'related_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
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

    protected static function newFactory(): CurrentAccountMovementFactory
    {
        return CurrentAccountMovementFactory::new();
    }
}
