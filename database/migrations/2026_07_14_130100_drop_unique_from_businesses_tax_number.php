<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('businesses'));

        $uniqueIndex = $indexes->first(
            fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['tax_number']
        );

        if ($uniqueIndex !== null) {
            Schema::table('businesses', function (Blueprint $table) use ($uniqueIndex): void {
                $table->dropUnique($uniqueIndex['name']);
            });
        }

        $indexes = collect(Schema::getIndexes('businesses'));
        $hasTaxIndex = $indexes->contains(
            fn (array $index): bool => ($index['columns'] ?? []) === ['tax_number']
        );

        if (! $hasTaxIndex) {
            Schema::table('businesses', function (Blueprint $table): void {
                $table->index('tax_number');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('businesses'));

        $nonUniqueTaxIndex = $indexes->first(
            fn (array $index): bool => ! ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['tax_number']
        );

        if ($nonUniqueTaxIndex !== null) {
            Schema::table('businesses', function (Blueprint $table) use ($nonUniqueTaxIndex): void {
                $table->dropIndex($nonUniqueTaxIndex['name']);
            });
        }

        $indexes = collect(Schema::getIndexes('businesses'));
        $hasUnique = $indexes->contains(
            fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['tax_number']
        );

        if (! $hasUnique) {
            Schema::table('businesses', function (Blueprint $table): void {
                $table->unique('tax_number');
            });
        }
    }
};
