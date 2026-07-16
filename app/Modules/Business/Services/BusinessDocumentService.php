<?php

namespace App\Modules\Business\Services;

use App\Core\Services\EntityDocumentStorageService;
use App\Models\Contract;
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
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->displayName(),
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

    public function storeForContract(Contract $contract, UploadedFile $file, User $user): Document
    {
        $document = DB::transaction(function () use ($contract, $file, $user): Document {
            $this->deleteContractDocuments($contract);

            $category = DocumentCategory::query()
                ->where('code', 'contract')
                ->firstOrFail();

            $stored = $this->storage->store($file, 'contracts', $contract->id);

            return Document::query()->create([
                'documentable_type' => Contract::class,
                'documentable_id' => $contract->id,
                'document_category_id' => $category->id,
                'original_name' => $stored['original_name'],
                'stored_name' => $stored['stored_name'],
                'file_path' => $stored['file_path'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'disk' => $stored['disk'],
                'uploaded_by' => $user->id,
            ]);
        });

        $contract->update(['document_id' => $document->id]);

        return $document;
    }

    public function findForContract(Contract $contract): ?Document
    {
        $contract->loadMissing('document');

        if ($contract->document !== null) {
            return $contract->document;
        }

        $direct = Document::query()
            ->where('documentable_type', Contract::class)
            ->where('documentable_id', $contract->id)
            ->orderByDesc('created_at')
            ->first();

        if ($direct !== null) {
            return $this->attachDocumentToContract($contract, $direct);
        }

        $business = $contract->contractable;

        if (! $business instanceof Business) {
            return null;
        }

        return $this->findLegacyBusinessContractDocument($contract, $business);
    }

    private function findLegacyBusinessContractDocument(Contract $contract, Business $business): ?Document
    {
        $documents = Document::query()
            ->where('documentable_type', Business::class)
            ->where('documentable_id', $business->id)
            ->whereHas('category', fn (Builder $category) => $category->where('code', 'contract'))
            ->orderByDesc('created_at')
            ->get();

        foreach ($documents as $document) {
            $alreadyLinked = Contract::query()
                ->whereKeyNot($contract->id)
                ->where('document_id', $document->id)
                ->exists();

            if ($alreadyLinked) {
                continue;
            }

            $match = Contract::query()
                ->where('contractable_type', Business::class)
                ->where('contractable_id', $business->id)
                ->where('created_at', '<=', $document->created_at)
                ->orderByDesc('created_at')
                ->first();

            if ($match?->id === $contract->id) {
                return $this->attachDocumentToContract($contract, $document);
            }
        }

        $unlinkedContracts = Contract::query()
            ->where('contractable_type', Business::class)
            ->where('contractable_id', $business->id)
            ->whereNull('document_id')
            ->count();

        if ($unlinkedContracts === 1 && $documents->isNotEmpty()) {
            return $this->attachDocumentToContract($contract, $documents->first());
        }

        return null;
    }

    private function attachDocumentToContract(Contract $contract, Document $document): Document
    {
        if ((int) $contract->document_id !== (int) $document->id) {
            $contract->update(['document_id' => $document->id]);
        }

        if ($document->documentable_type !== Contract::class || (int) $document->documentable_id !== (int) $contract->id) {
            $document->update([
                'documentable_type' => Contract::class,
                'documentable_id' => $contract->id,
            ]);
        }

        return $document->fresh();
    }

    public function deleteContractDocuments(Contract $contract): void
    {
        if ($contract->document_id !== null) {
            $document = Document::query()->find($contract->document_id);

            if ($document !== null) {
                $this->destroy($document);
            }
        }

        Document::query()
            ->where('documentable_type', Contract::class)
            ->where('documentable_id', $contract->id)
            ->get()
            ->each(fn (Document $document) => $this->destroy($document));

        $contract->update(['document_id' => null]);
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
