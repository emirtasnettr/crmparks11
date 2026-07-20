<?php

use App\Core\Enums\Status;
use App\Models\User;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $roleId = DB::table('roles')
            ->where('name', 'courier')
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId === null) {
            return;
        }

        $orphanIds = DB::table('users')
            ->join('model_has_roles', function ($join) use ($roleId): void {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.role_id', $roleId)
                    ->where('model_has_roles.model_type', User::class);
            })
            ->whereNull('users.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('users.profileable_type')
                    ->orWhere('users.profileable_type', '!=', Courier::class)
                    ->orWhereNull('users.profileable_id');
            })
            ->pluck('users.id');

        if ($orphanIds->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('users')
            ->whereIn('id', $orphanIds)
            ->update([
                'status' => Status::Inactive->value,
                'deleted_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Soft-deleted orphan accounts are not restored automatically.
    }
};
