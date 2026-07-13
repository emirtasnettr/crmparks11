<?php

namespace App\Modules\FormBuilder\Models;

use App\Modules\LandingPage\Models\LandingPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'form_id',
        'form_submission_status_id',
        'landing_page_id',
        'landing_page_slug',
        'landing_page_name',
        'data',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(FormSubmissionStatus::class, 'form_submission_status_id');
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(FormSubmissionNote::class)->latest();
    }

    /**
     * @return array<string, mixed>
     */
    public function toRecordArray(): array
    {
        $status = $this->relationLoaded('status') ? $this->status : $this->status()->first();

        return [
            'id' => $this->id,
            'form_id' => $this->form_id,
            'form_submission_status_id' => $this->form_submission_status_id,
            'status' => $status ? [
                'id' => $status->id,
                'name' => $status->name,
                'slug' => $status->slug,
                'color' => $status->color ?? 'muted',
                'is_default' => (bool) $status->is_default,
            ] : null,
            'landing_page_id' => $this->landing_page_id,
            'landing_page_slug' => $this->landing_page_slug,
            'landing_page_name' => $this->landing_page_name,
            'data' => $this->data ?? [],
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
        ];
    }
}
