<?php

namespace App\Modules\ActivityLog\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory, HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'description', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): ActivityLogFactory
    {
        return ActivityLogFactory::new();
    }
}
