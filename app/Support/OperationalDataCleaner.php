<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Operasyonel / test verisini temizler; süper admin kullanıcıları ve sistem kataloglarını bırakır.
 * Yalnızca local/testing ortamında çalışır.
 */
final class OperationalDataCleaner
{
    /**
     * Silinecek tablolar (FK sırası önemli değil — foreign key'ler kapatılır).
     *
     * @return list<string>
     */
    public static function tablesToWipe(): array
    {
        return [
            'activity_logs',
            'business_shift_day_couriers',
            'business_shift_couriers',
            'business_shifts',
            'finance_collection_payments',
            'finance_collections',
            'finance_payment_lines',
            'finance_payments',
            'finance_invoices',
            'finance_revenues',
            'finance_expenses',
            'current_account_movements',
            'current_accounts',
            'earning_lines',
            'earning_batches',
            'business_contacts',
            'business_pricings',
            'agency_contacts',
            'courier_bank_accounts',
            'courier_vehicles',
            'documents',
            'contracts',
            'stock_assignments',
            'stock_products',
            'form_submission_notes',
            'form_submissions',
            'forms',
            'landing_pages',
            'policies',
            'notifications',
            'notification_preferences',
            'period_locks',
            'personal_access_tokens',
            'password_reset_tokens',
            'sessions',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
            'businesses',
            'agencies',
            'couriers',
            'business_shift_attendances',
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function wipeKeepingSuperAdmins(): array
    {
        DemoDataGuard::assertAllowed();

        $counts = [];

        DB::transaction(function () use (&$counts): void {
            Schema::disableForeignKeyConstraints();

            foreach (self::tablesToWipe() as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $counts[$table] = (int) DB::table($table)->count();
                DB::table($table)->delete();
            }

            $superAdminIds = User::query()
                ->withTrashed()
                ->role('super_admin')
                ->pluck('id')
                ->all();

            if ($superAdminIds === []) {
                Schema::enableForeignKeyConstraints();

                throw new \RuntimeException('Sistemde süper admin bulunamadı; temizlik iptal edildi.');
            }

            $usersQuery = User::query()->withTrashed()->whereNotIn('id', $superAdminIds);
            $counts['users'] = (int) $usersQuery->count();

            $userIds = $usersQuery->pluck('id')->all();

            if ($userIds !== []) {
                if (Schema::hasTable('model_has_roles')) {
                    DB::table('model_has_roles')
                        ->where('model_type', User::class)
                        ->whereIn('model_id', $userIds)
                        ->delete();
                }

                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')
                        ->where('model_type', User::class)
                        ->whereIn('model_id', $userIds)
                        ->delete();
                }

                User::query()->withTrashed()->whereIn('id', $userIds)->forceDelete();
            }

            // Soft-deleted süper adminleri geri getir / aktif tut
            User::query()->withTrashed()->whereIn('id', $superAdminIds)->restore();

            Schema::enableForeignKeyConstraints();
        });

        return array_filter($counts, fn (int $count): bool => $count > 0);
    }
}
