<?php

use App\Models\Contract;
use App\Models\Document;
use App\Modules\Business\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('document_id')
                ->nullable()
                ->after('notes')
                ->constrained('documents')
                ->nullOnDelete();
        });

        Document::query()
            ->where('documentable_type', Contract::class)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('documentable_id')
            ->each(function ($documents, $contractId): void {
                $document = $documents->first();

                if ($document === null) {
                    return;
                }

                Contract::query()
                    ->whereKey($contractId)
                    ->whereNull('document_id')
                    ->update(['document_id' => $document->id]);
            });

        Document::query()
            ->where('documentable_type', Business::class)
            ->whereHas('category', fn ($query) => $query->where('code', 'contract'))
            ->orderBy('created_at')
            ->get()
            ->each(function (Document $document): void {
                $contract = Contract::query()
                    ->where('contractable_type', Business::class)
                    ->where('contractable_id', $document->documentable_id)
                    ->where('created_at', '<=', $document->created_at)
                    ->whereNull('document_id')
                    ->orderByDesc('created_at')
                    ->first();

                if ($contract === null) {
                    $contract = Contract::query()
                        ->where('contractable_type', Business::class)
                        ->where('contractable_id', $document->documentable_id)
                        ->whereNull('document_id')
                        ->orderByDesc('created_at')
                        ->first();
                }

                if ($contract === null) {
                    return;
                }

                $contract->update(['document_id' => $document->id]);

                $document->update([
                    'documentable_type' => Contract::class,
                    'documentable_id' => $contract->id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_id');
        });
    }
};
