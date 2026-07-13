<?php

namespace App\Modules\FormBuilder\Services;

use App\Modules\FormBuilder\Models\FormSubmissionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FormSubmissionStatusService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $this->ensureDefaults();

        return FormSubmissionStatus::query()
            ->withCount('submissions')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (FormSubmissionStatus $status) => $status->toRecordArray())
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultStatus(): array
    {
        $this->ensureDefaults();

        $status = FormSubmissionStatus::query()
            ->withCount('submissions')
            ->where('is_default', true)
            ->first();

        if ($status === null) {
            $status = FormSubmissionStatus::query()->withCount('submissions')->orderBy('sort_order')->orderBy('id')->first();
            if ($status !== null) {
                $status->update(['is_default' => true]);
            }
        }

        if ($status === null) {
            throw ValidationException::withMessages([
                'status' => 'Başvuru statüsü bulunamadı.',
            ]);
        }

        return $status->toRecordArray();
    }

    public function defaultStatusId(): int
    {
        return (int) $this->defaultStatus()['id'];
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, ?string $color = null): array
    {
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Statü adı zorunludur.',
            ]);
        }

        $this->assertUniqueName($name);

        $maxOrder = (int) FormSubmissionStatus::query()->max('sort_order');

        $status = FormSubmissionStatus::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'color' => $color ?: 'muted',
            'sort_order' => $maxOrder + 1,
            'is_default' => FormSubmissionStatus::query()->count() === 0,
        ]);

        $status->loadCount('submissions');

        return $status->toRecordArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, string $name, ?string $color = null): array
    {
        $status = FormSubmissionStatus::query()->findOrFail($id);
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Statü adı zorunludur.',
            ]);
        }

        $this->assertUniqueName($name, $id);

        $status->name = $name;
        if ($color !== null && $color !== '') {
            $status->color = $color;
        }
        $status->save();
        $status->loadCount('submissions');

        return $status->toRecordArray();
    }

    public function delete(int $id): void
    {
        $status = FormSubmissionStatus::query()->withCount('submissions')->findOrFail($id);

        if ($status->submissions_count > 0) {
            throw ValidationException::withMessages([
                'status' => 'Bu statüde başvuru olduğu için silinemez.',
            ]);
        }

        if (FormSubmissionStatus::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'status' => 'En az bir başvuru statüsü bulunmalıdır.',
            ]);
        }

        $wasDefault = $status->is_default;

        $status->delete();

        if ($wasDefault) {
            $next = FormSubmissionStatus::query()->orderBy('sort_order')->orderBy('id')->first();
            $next?->update(['is_default' => true]);
        }
    }

    public function setDefault(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $status = FormSubmissionStatus::query()->withCount('submissions')->findOrFail($id);

            FormSubmissionStatus::query()->where('is_default', true)->update(['is_default' => false]);
            $status->update(['is_default' => true]);
            $status->refresh();
            $status->loadCount('submissions');

            return $status->toRecordArray();
        });
    }

    public function ensureDefaults(): void
    {
        if (FormSubmissionStatus::query()->exists()) {
            return;
        }

        $now = now();
        $defaults = [
            ['name' => 'Yeni Başvuru', 'slug' => 'yeni-basvuru', 'color' => 'primary', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Olumlu', 'slug' => 'olumlu', 'color' => 'success', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Olumsuz', 'slug' => 'olumsuz', 'color' => 'danger', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Ulaşılamadı', 'slug' => 'ulasilamadi', 'color' => 'warning', 'sort_order' => 4, 'is_default' => false],
            ['name' => 'Kararsız', 'slug' => 'kararsiz', 'color' => 'muted', 'sort_order' => 5, 'is_default' => false],
        ];

        foreach ($defaults as $status) {
            FormSubmissionStatus::query()->create([
                ...$status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function assertUniqueName(string $name, ?int $ignoreId = null): void
    {
        $exists = FormSubmissionStatus::query()
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Bu statü adı zaten kullanılıyor.',
            ]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'statu';
        $slug = $base;
        $counter = 2;

        while (FormSubmissionStatus::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
