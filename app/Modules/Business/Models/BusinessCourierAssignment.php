<?php

namespace App\Modules\Business\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use Database\Factories\BusinessCourierAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessCourierAssignment extends Model
{
    /** @use HasFactory<BusinessCourierAssignmentFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'courier_id',
        'start_date',
        'end_date',
        'status',
        'notes',
        'assigned_by',
        'ended_by',
        'ended_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Currently active assignments (not terminated / not past end date).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where('status', 'active')
            ->where(function (Builder $inner) use ($today): void {
                $inner->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    protected static function newFactory(): BusinessCourierAssignmentFactory
    {
        return BusinessCourierAssignmentFactory::new();
    }
}
