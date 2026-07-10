<?php

namespace App\Modules\Agency\Services;

use App\Core\Services\EntityDocumentStorageService;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgencyDocumentService
{
    public function __construct(
        private readonly AgencyDocumentPresenter $presenter,
        private readonly EntityDocumentStorageService $storage,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Document>
     */
    public function filter(array $filters): Collection
    {
        $documents = $this->baseQuery($filters)
            ->with(['documentable.city', 'category', 'uploader'])
            ->orderByDesc('created_at')
            ->get();

        return $this->applyPresentationFilters($documents, $filters);
    }

    public function find(int $id): ?Document
    {
        return Document::query()
            ->where('documentable_type', Agency::class)
            ->with(['documentable.city', 'category', 'uploader'])
            ->find($id);
    }

    /**
     * @return Collection<int, Document>
     */
    public function forAgency(int $agencyId): Collection
    {
        return Document::query()
            ->where('documentable_type', Agency::class)
            ->where('documentable_id', $agencyId)
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
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->displayName(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $file, User $user): Document
    {
        return DB::transaction(function () use ($data, $file, $user): Document {
            $agency = Agency::query()->findOrFail((int) $data['agency_id']);
            $category = DocumentCategory::query()
                ->where('code', $data['document_type'])
                ->firstOrFail();

            $stored = $this->storage->store($file, 'agency', $agency->id);

            return Document::query()->create([
                'documentable_type' => Agency::class,
                'documentable_id' => $agency->id,
                'document_category_id' => $category->id,
                'original_name' => $stored['original_name'],
                'stored_name' => $stored['stored_name'],
                'file_path' => $stored['file_path'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'disk' => $stored['disk'],
                'uploaded_by' => $user->id,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
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
            ->where('documentable_type', Agency::class)
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('documentable_id', (int) $filters['agency_id']);
            })
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(original_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereHasMorph('documentable', [Agency::class], function (Builder $agency) use ($search): void {
                            $agency->whereRaw('LOWER(company_name) LIKE ?', ['%'.$search.'%']);
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
}
