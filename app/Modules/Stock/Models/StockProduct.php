<?php

namespace App\Modules\Stock\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockProduct extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'quantity',
        'unit',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StockAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedQuantity(): int
    {
        return (int) $this->assignments()
            ->where('status', 'assigned')
            ->sum('quantity');
    }
}
