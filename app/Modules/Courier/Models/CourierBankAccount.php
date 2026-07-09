<?php

namespace App\Modules\Courier\Models;

use App\Core\Traits\HasUuid;
use Database\Factories\CourierBankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourierBankAccount extends Model
{
    /** @use HasFactory<CourierBankAccountFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'courier_id',
        'bank_key',
        'account_holder',
        'iban',
        'branch_code',
        'account_number',
        'is_default',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    protected static function newFactory(): CourierBankAccountFactory
    {
        return CourierBankAccountFactory::new();
    }
}
