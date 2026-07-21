<?php

namespace App\Modules\Finance\Models;

use App\Models\EarningLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialAdjustment extends Model
{
    protected $fillable = [
        'target_type',
        'target_id',
        'current_account_id',
        'earning_line_id',
        'direction',
        'amount',
        'reason',
        'created_by',
        'current_account_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function currentAccount(): BelongsTo
    {
        return $this->belongsTo(CurrentAccount::class);
    }

    public function earningLine(): BelongsTo
    {
        return $this->belongsTo(EarningLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(CurrentAccountMovement::class, 'current_account_movement_id');
    }

    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }

    public function directionLabel(): string
    {
        return $this->isCredit() ? 'Ekleme' : 'Düşürme';
    }
}
