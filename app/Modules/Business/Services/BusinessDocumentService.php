<?php

namespace App\Modules\Business\Services;

use App\Core\Services\EntityDocumentStorageService;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessDocumentService
{
    public function __construct(
        private readonly BusinessDocumentPresenter $presenter,
        private readonly EntityDocumentStorageService $storage,
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

    /**
     * @return Collection<int, Document>
     */
    public function forBusiness(int $businessId): Collection
    {
        return Document::query()
            ->where('documentable_type', Business::class)
            ->where('documentable_id', $businessId)
            ->with(['documentable', 'category', 'uploader'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->company_name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $file, User $user): Document
    {
        return DB::transaction(function () use ($data, $file, $user): Document {
            $business = Business::query()->findOrFail((int) $data['business_id']);
            $category = DocumentCategory::query()
                ->where('code', $data['document_type'])
                ->firstOrFail();

            $stored = $this->storage->store($file, 'business', $business->id);

            return Document::query()->create([
                'documentable_type' => Business::class,
                'documentable_id' => $business->id,
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

    public function storeContractFile(Business $business, UploadedFile $file, User $user): Document
    {
        return $this->create([
            'business_id' => $business->id,
            'document_type' => 'contract',
        ], $file, $user);
    }

    public function find(int $id): ?Document
    {
        return Document::query()
            ->where('documentable_type', Business::class)
            ->with(['documentable', 'category', 'uploader'])
            ->find($id);
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
            ->where('documentable_type', Business::class)
            ->when(! empty($filters['business_id']) && $filters['business_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('documentable_id', (int) $filters['business_id']);
            })
            ->when(! empty($filters['document_type']) && $filters['document_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('category', fn (Builder $category) => $category->where('code', $filters['document_type']));
            })
            ->when(! empty($filters['date_range']) && $filters['date_range'] !== 'all', function (Builder $query) use ($filters): void {
                $today = Carbon::today();

                match ($filters['date_range']) {
                    'last_7_days' => $query->where('created_at', '>=', $today->copy()->subDays(7)),
                    'last_30_days' => $query->where('created_at', '>=', $today->copy()->subDays(30)),
                    'this_month' => $query
                        ->whereMonth('created_at', $today->month)
                        ->whereYear('created_at', $today->year),
                    'last_3_months' => $query->where('created_at', '>=', $today->copy()->subMonths(3)),
                    'this_year' => $query->whereYear('created_at', $today->year),
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
