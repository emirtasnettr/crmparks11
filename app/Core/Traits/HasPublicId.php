<?php

namespace App\Core\Traits;

use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

trait HasPublicId
{
    /**
     * @return list<class-string<Model>>
     */
    public static function publicIdEntityModels(): array
    {
        return [
            Business::class,
            Courier::class,
            Agency::class,
        ];
    }

    protected static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            if (! empty($model->public_id)) {
                if (static::publicIdIsTaken((string) $model->public_id, $model)) {
                    throw new RuntimeException('Bu kayıt numarası başka bir kayıtta kullanılıyor.');
                }

                return;
            }

            if (! Schema::hasColumn($model->getTable(), 'public_id')) {
                return;
            }

            $model->public_id = static::generateUniquePublicId();
        });
    }

    public static function generateUniquePublicId(int $maxAttempts = 50): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // En fazla 8 hane: 10.000.000 – 99.999.999
            $candidate = (string) random_int(10_000_000, 99_999_999);

            if (! static::publicIdIsTaken($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Benzersiz kayıt numarası üretilemedi.');
    }

    public static function publicIdIsTaken(string $publicId, ?Model $except = null): bool
    {
        foreach (static::publicIdEntityModels() as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }

            /** @var Model $probe */
            $probe = new $modelClass;

            if (! Schema::hasTable($probe->getTable()) || ! Schema::hasColumn($probe->getTable(), 'public_id')) {
                continue;
            }

            $query = $modelClass::query()->withTrashed()->where('public_id', $publicId);

            if (
                $except !== null
                && $except instanceof $modelClass
                && $except->getKey() !== null
            ) {
                $query->whereKeyNot($except->getKey());
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }
}
