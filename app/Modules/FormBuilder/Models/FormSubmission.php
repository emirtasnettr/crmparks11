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
        return [
            'id' => $this->id,
            'form_id' => $this->form_id,
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
