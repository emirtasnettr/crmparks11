<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $columns = [
                'mersis_number',
                'trade_registry_number',
                'address',
                'website',
                'tax_office',
                'email',
                'commission_rate',
                'payment_period',
                'bank_key',
                'account_holder',
                'iban',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('agencies', $column),
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'tax_office')) {
                $table->string('tax_office')->nullable()->after('brand_name');
            }
            if (! Schema::hasColumn('agencies', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('agencies', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
            if (! Schema::hasColumn('agencies', 'mersis_number')) {
                $table->string('mersis_number')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'trade_registry_number')) {
                $table->string('trade_registry_number')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('agencies', 'payment_period')) {
                $table->string('payment_period')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'bank_key')) {
                $table->string('bank_key')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'account_holder')) {
                $table->string('account_holder')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'iban')) {
                $table->string('iban')->nullable();
            }
        });
    }
};
