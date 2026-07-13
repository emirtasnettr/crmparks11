<?php

namespace App\Modules\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmissionStatus extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'sort_order',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_submission_status_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toRecordArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color ?? 'muted',
            'sort_order' => (int) $this->sort_order,
            'is_default' => (bool) $this->is_default,
            'submissions_count' => (int) ($this->submissions_count ?? $this->submissions()->count()),
            'can_delete' => (int) ($this->submissions_count ?? $this->submissions()->count()) === 0,
        ];
    }
}
