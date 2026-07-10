<?php

namespace App\Modules\Agency\Services;

use App\Core\Services\EntityDocumentStorageService;
use App\Models\Document;
use App\Modules\Agency\Models\Agency;
use App\Support\DocumentPresentation;
use Carbon\Carbon;

class AgencyDocumentPresenter
{
    public const EXPIRY_WARNING_DAYS = 30;

    public function __construct(
        private readonly EntityDocumentStorageService $storage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexRow(Document $document): array
    {
        return $this->enrich($document);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(Document $document): array
    {
        return array_merge($this->enrich($document), [
            'version_history' => [],
        ]);
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
        $document->loadMissing(['documentable.city', 'category', 'uploader']);

        /** @var Agency|null $agency */
        $agency = $document->documentable;
        $agency?->loadMissing('city');
        $extension = DocumentPresentation::extensionFromName($document->original_name);
        $uploadedAt = $document->created_at ?? now();
        $expiryDate = $document->expires_at;
        $status = $this->resolveStatus($document);
        $daysRemaining = $expiryDate
            ? (int) Carbon::today()->diffInDays($expiryDate->startOfDay(), false)
            : null;

        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'agency_id' => $agency?->id,
            'agency_name' => $agency?->displayName() ?? '—',
            'agency_city' => $agency?->city?->name ?? '—',
            'agency_phone' => $agency?->phone ?? '—',
            'agency_email' => $agency?->email ?? '—',
            'agency_authorized' => $agency?->authorized_person ?? '—',
            'document_type' => $document->category?->code ?? 'other',
            'document_type_label' => $document->category?->label ?? 'Diğer',
            'document_number' => pathinfo($document->original_name, PATHINFO_FILENAME),
            'file_name' => $document->original_name,
            'file_extension' => $extension,
            'file_url' => $this->storage->url($document->file_path),
            'uploaded_at' => $uploadedAt->toDateString(),
            'uploaded_at_formatted' => $uploadedAt->format('d.m.Y'),
            'expiry_date' => $expiryDate?->toDateString(),
            'expiry_date_formatted' => $expiryDate?->format('d.m.Y') ?? '—',
            'status' => $status,
            'status_label' => \App\Modules\Agency\Data\AgencyDocumentFormData::statuses()[$status] ?? $status,
            'days_remaining' => $daysRemaining,
            'version' => 1,
            'is_current' => true,
        ];
    }

    private function resolveStatus(Document $document): string
    {
        if ($document->expires_at === null) {
            return 'valid';
        }

        $today = Carbon::today();
        $expiry = $document->expires_at->startOfDay();

        if ($expiry->lt($today)) {
            return 'expired';
        }

        if ($today->diffInDays($expiry, false) <= self::EXPIRY_WARNING_DAYS) {
            return 'expiring_soon';
        }

        return 'valid';
    }
}
