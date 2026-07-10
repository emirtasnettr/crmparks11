<?php

namespace App\Modules\Courier\Services;

use App\Core\Services\EntityDocumentStorageService;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourierDocumentService
{
    public function __construct(
        private readonly CourierDocumentPresenter $presenter,
        private readonly EntityDocumentStorageService $storage,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Document>
     */
    public function filter(array $filters): Collection
    {
        $documents = $this->baseQuery($filters)
            ->with(['documentable', 'category', 'uploader'])
            ->orderByDesc('created_at')
            ->get();

        return $this->applyPresentationFilters($documents, $filters);
    }

    public function find(int $id): ?Document
    {
        return Document::query()
            ->where('documentable_type', Courier::class)
            ->with(['documentable', 'category', 'uploader'])
            ->find($id);
    }

    /**
     * @return Collection<int, Document>
     */
    public function forCourier(int $courierId): Collection
    {
        return Document::query()
            ->where('documentable_type', Courier::class)
            ->where('documentable_id', $courierId)
            ->with(['documentable', 'category', 'uploader'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summary(array $filters): array
    {
        $items = $this->filter($filters)
            ->map(fn (Document $document) => $this->presenter->indexRow($document));

        return [
            'total' => $items->count(),
            'valid' => $items->where('status', 'valid')->count(),
            'expiring_soon' => $items->where('status', 'expiring_soon')->count(),
            'expired' => $items->where('status', 'expired')->count(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $file, User $user): Document
    {
        return DB::transaction(function () use ($data, $file, $user): Document {
            $courier = Courier::query()->findOrFail((int) $data['courier_id']);
            $category = DocumentCategory::query()
                ->where('code', $data['document_type'])
                ->firstOrFail();

            $stored = $this->storage->store($file, 'courier', $courier->id);
            $originalName = $this->resolveOriginalName($data, $stored['original_name']);

            $document = Document::query()->create([
                'documentable_type' => Courier::class,
                'documentable_id' => $courier->id,
                'document_category_id' => $category->id,
                'original_name' => $originalName,
                'stored_name' => $stored['stored_name'],
                'file_path' => $stored['file_path'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'disk' => $stored['disk'],
                'uploaded_by' => $user->id,
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $this->activityLog->log(
                'document_uploaded',
                $document,
                description: "{$courier->full_name} için yeni belge yüklendi.",
            );

            return $document;
        });
    }

    public function destroy(Document $document): void
    {
        DB::transaction(function () use ($document): void {
            $this->storage->delete($document->file_path, $document->disk ?: 'public');
            $document->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return Document::query()
            ->where('documentable_type', Courier::class)
            ->when(! empty($filters['courier_id']) && $filters['courier_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('documentable_id', (int) $filters['courier_id']);
            })
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(original_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereHasMorph('documentable', [Courier::class], function (Builder $courier) use ($search): void {
                            $courier->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%']);
                        })
                        ->orWhereHas('category', function (Builder $category) use ($search): void {
                            $category->whereRaw('LOWER(label) LIKE ?', ['%'.$search.'%']);
                        });
                });
            })
            ->when(! empty($filters['document_type']) && $filters['document_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('category', fn (Builder $category) => $category->where('code', $filters['document_type']));
            })
            ->when(! empty($filters['expiry_filter']) && $filters['expiry_filter'] !== 'all', function (Builder $query) use ($filters): void {
                $today = Carbon::today();

                match ($filters['expiry_filter']) {
                    'expiring_soon' => $query
                        ->whereNotNull('expires_at')
                        ->whereDate('expires_at', '>=', $today)
                        ->whereDate('expires_at', '<=', $today->copy()->addDays(30)),
                    'expired' => $query
                        ->whereNotNull('expires_at')
                        ->whereDate('expires_at', '<', $today),
                    'this_month' => $query
                        ->whereNotNull('expires_at')
                        ->whereMonth('expires_at', $today->month)
                        ->whereYear('expires_at', $today->year),
                    'next_3_months' => $query
                        ->whereNotNull('expires_at')
                        ->whereDate('expires_at', '>=', $today)
                        ->whereDate('expires_at', '<=', $today->copy()->addMonths(3)),
                    default => null,
                };
            });
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Document>
     */
    private function applyPresentationFilters(Collection $documents, array $filters): Collection
    {
        if (empty($filters['status']) || $filters['status'] === 'all') {
            return $documents;
        }

        return $documents
            ->filter(fn (Document $document) => $this->presenter->displayStatus($document) === $filters['status'])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveOriginalName(array $data, string $fallbackName): string
    {
        $documentNumber = trim((string) ($data['document_number'] ?? ''));

        if ($documentNumber === '') {
            return $fallbackName;
        }

        $extension = pathinfo($fallbackName, PATHINFO_EXTENSION);

        if ($extension !== '' && ! str_ends_with(mb_strtolower($documentNumber), '.'.mb_strtolower($extension))) {
            return $documentNumber.'.'.$extension;
        }

        return $documentNumber;
    }
}
