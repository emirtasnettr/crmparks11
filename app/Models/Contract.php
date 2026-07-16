<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'contractable_type',
        'contractable_id',
        'contract_type_id',
        'title',
        'contract_number',
        'start_date',
        'end_date',
        'auto_reminder',
        'reminder_days_before',
        'status',
        'notes',
        'document_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'auto_reminder' => 'boolean',
        ];
    }

    public function contractable(): MorphTo
    {
        return $this->morphTo();
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    protected static function newFactory(): ContractFactory
    {
        return ContractFactory::new();
    }
}
