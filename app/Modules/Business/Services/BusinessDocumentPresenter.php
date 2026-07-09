<?php

namespace App\Modules\Business\Services;

use App\Models\Document;
use App\Modules\Business\Models\Business;
use App\Support\DocumentPresentation;
use Carbon\Carbon;

class BusinessDocumentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(Document $document): array
    {
        return $this->enrich($document);
    }

    public function displayStatus(Document $document): string
    {
        return $this->resolveStatus($document);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(Document $document): array
    {
        $document->loadMissing(['documentable', 'category', 'uploader']);

        /** @var Business|null $business */
        $business = $document->documentable;
        $extension = DocumentPresentation::extensionFromName($document->original_name);
        $uploadedAt = $document->created_at ?? now();
        $status = $this->resolveStatus($document);

        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'business_id' => $business?->id,
            'business_name' => $business?->company_name ?? '—',
            'name' => pathinfo($document->original_name, PATHINFO_FILENAME),
            'document_type' => $document->category?->code ?? 'other',
            'document_type_label' => $document->category?->label ?? 'Diğer',
            'file_name' => $document->original_name,
            'file_extension' => $extension,
            'file_size_bytes' => (int) $document->file_size,
            'file_size_formatted' => DocumentPresentation::formatFileSize((int) $document->file_size),
            'uploaded_at' => $uploadedAt->toDateString(),
            'uploaded_at_formatted' => $uploadedAt->format('d.m.Y'),
            'uploaded_by' => $document->uploader?->name ?? '—',
            'status' => $status,
            'file_type_label' => DocumentPresentation::fileTypeLabel($extension),
            'description' => null,
        ];
    }

    private function resolveStatus(Document $document): string
    {
        if ($document->expires_at === null) {
            return 'active';
        }

        $today = Carbon::today();
        $expiry = $document->expires_at->startOfDay();

        if ($expiry->lt($today)) {
            return 'expired';
        }

        if ($today->diffInDays($expiry, false) <= 30) {
            return 'pending';
        }

        return 'active';
    }
}
