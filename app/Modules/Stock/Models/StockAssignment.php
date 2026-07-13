<?php

namespace App\Modules\Stock\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAssignment extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'stock_product_id',
        'courier_id',
        'quantity',
        'assigned_at',
        'returned_at',
        'status',
        'notes',
        'assigned_by',
        'returned_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'assigned_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StockProduct::class, 'stock_product_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }
}
