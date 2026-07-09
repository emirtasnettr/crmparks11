<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $original = fake()->words(3, true).'.pdf';

        return [
            'documentable_type' => Business::class,
            'documentable_id' => Business::factory(),
            'document_category_id' => DocumentCategory::query()->where('code', 'other')->value('id'),
            'original_name' => $original,
            'stored_name' => Str::uuid().'.pdf',
            'file_path' => 'documents/business/1/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100_000, 2_000_000),
            'disk' => 'public',
            'uploaded_by' => User::factory(),
            'expires_at' => null,
        ];
    }
}
