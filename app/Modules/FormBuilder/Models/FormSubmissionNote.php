<?php

namespace App\Modules\FormBuilder\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionNote extends Model
{
    protected $fillable = [
        'form_submission_id',
        'user_id',
        'body',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toRecordArray(): array
    {
        return [
            'id' => $this->id,
            'form_submission_id' => $this->form_submission_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'body' => $this->body,
            'created_at' => $this->created_at?->toDateTimeString(),
            'created_at_formatted' => $this->created_at?->format('d.m.Y H:i'),
        ];
    }
}
