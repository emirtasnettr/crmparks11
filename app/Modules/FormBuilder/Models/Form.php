<?php

namespace App\Modules\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'status',
        'fields',
        'notify_user_ids',
        'notify_roles',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'notify_user_ids' => 'array',
            'notify_roles' => 'array',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toRecordArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?? '',
            'status' => $this->status,
            'fields' => $this->fields ?? [],
            'notify_user_ids' => array_values(array_map('intval', $this->notify_user_ids ?? [])),
            'notify_roles' => array_values(array_map('strval', $this->notify_roles ?? [])),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
