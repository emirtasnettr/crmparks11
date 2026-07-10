<?php

namespace App\Modules\Policy\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'key',
        'slug',
        'title',
        'content',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toRecordArray(): array
    {
        return [
            'key' => $this->key,
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content ?? '',
            'meta_title' => $this->meta_title ?? $this->title,
            'meta_description' => $this->meta_description ?? '',
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
