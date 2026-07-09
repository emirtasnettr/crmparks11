<?php

namespace App\Modules\Finance\Models;

use App\Core\Traits\HasUuid;
use Database\Factories\CurrentAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrentAccount extends Model
{
    /** @use HasFactory<CurrentAccountFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'code',
        'account_type',
        'accountable_type',
        'accountable_id',
        'title',
        'phone',
        'email',
        'tax_number',
        'city',
        'address',
        'status',
    ];

    public function accountable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<CurrentAccountMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CurrentAccountMovement::class)->orderByDesc('transaction_date')->orderByDesc('id');
    }

    protected static function newFactory(): CurrentAccountFactory
    {
        return CurrentAccountFactory::new();
    }
}
