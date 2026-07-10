<?php

namespace App\Modules\Business\Services;

use App\Core\Imports\SheetArrayImport;
use App\Models\User;
use App\Modules\Business\Data\BusinessEarningFormData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class BusinessEarningImportService
{
    public function __construct(
        private readonly BusinessEarningService $earnings,
    ) {}

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function templateSheet(): array
    {
        return [
            'headings' => [
                'isletme_id',
                'kurye_id',
                'ay',
                'yil',
                'calisma_modeli',
                'paket_sayisi',
                'isletme_birim_fiyat',
                'kurye_birim_fiyat',
                'gelir_toplam',
                'kurye_odeme',
                'ekstra_gelir',
                'ekstra_gider',
                'kesinti',
                'aciklama',
                'durum',
            ],
            'rows' => [
                [1, 1, 7, 2026, 'per_package', 100, 50, 40, '', '', 0, 0, 0, 'Örnek paket başı satır', 'draft'],
                [1, 1, 7, 2026, 'monthly_fixed', '', '', '', 15000, 12000, 0, 0, 0, 'Örnek aylık sabit satır', 'pending'],
            ],
        ];
    }

    /**
     * @return array{imported: int, failed: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file, User $user): array
    {
        $sheets = Excel::toArray(new SheetArrayImport, $file);
        $rows = $sheets[0] ?? [];

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Excel dosyası boş.',
            ]);
        }

        $headerRow = array_shift($rows);
        $map = $this->headerMap(array_map(fn ($value) => (string) $value, $headerRow));

        if (! isset($map['business_id'], $map['courier_id'], $map['period_month'], $map['period_year'], $map['pricing_model'])) {
            throw ValidationException::withMessages([
                'file' => 'Şablon başlıkları eksik. Lütfen güncel şablonu indirip kullanın.',
            ]);
        }

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $map, $user, &$imported, &$errors): void {
            foreach ($rows as $index => $row) {
                $lineNumber = $index + 2;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $payload = $this->mapRow($row, $map);

                $validator = Validator::make($payload, [
                    'business_id' => ['required', 'integer', 'exists:businesses,id'],
                    'courier_id' => ['required', 'integer', 'exists:couriers,id'],
                    'period_month' => ['required', 'integer', 'between:1,12'],
                    'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
                    'pricing_model' => ['required', Rule::in(array_keys(BusinessEarningFormData::pricingModels()))],
                    'package_count' => ['nullable', 'integer', 'min:0'],
                    'revenue_unit_price' => ['nullable', 'numeric', 'min:0'],
                    'courier_unit_price' => ['nullable', 'numeric', 'min:0'],
                    'revenue_total' => ['nullable', 'numeric', 'min:0'],
                    'courier_payment' => ['nullable', 'numeric', 'min:0'],
                    'extra_income' => ['nullable', 'numeric', 'min:0'],
                    'extra_expense' => ['nullable', 'numeric', 'min:0'],
                    'deduction' => ['nullable', 'numeric', 'min:0'],
                    'description' => ['nullable', 'string', 'max:2000'],
                    'status' => ['nullable', Rule::in(array_keys(BusinessEarningFormData::statuses()))],
                ]);

                if ($validator->fails()) {
                    $errors[] = "Satır {$lineNumber}: ".$validator->errors()->first();

                    continue;
                }

                $data = $validator->validated();
                $data['status'] = $data['status'] ?? 'draft';

                if (in_array($data['status'], ['approved', 'paid', 'cancelled'], true)) {
                    $data['status'] = 'draft';
                }

                try {
                    $this->earnings->create($data, $user);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Satır {$lineNumber}: ".$e->getMessage();
                }
            }
        });

        if ($imported === 0 && $errors !== []) {
            throw ValidationException::withMessages([
                'file' => 'Hiçbir satır içe aktarılamadı. '.$errors[0],
            ]);
        }

        return [
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, int>
     */
    private function headerMap(array $headers): array
    {
        $aliases = [
            'business_id' => ['business_id', 'isletme_id', 'işletme_id', 'isletme id'],
            'courier_id' => ['courier_id', 'kurye_id', 'kurye id'],
            'period_month' => ['period_month', 'ay', 'month'],
            'period_year' => ['period_year', 'yil', 'yıl', 'year'],
            'pricing_model' => ['pricing_model', 'calisma_modeli', 'çalışma_modeli', 'model'],
            'package_count' => ['package_count', 'paket_sayisi', 'paket_sayısı'],
            'revenue_unit_price' => ['revenue_unit_price', 'isletme_birim_fiyat', 'işletme_birim_fiyat'],
            'courier_unit_price' => ['courier_unit_price', 'kurye_birim_fiyat'],
            'revenue_total' => ['revenue_total', 'gelir_toplam'],
            'courier_payment' => ['courier_payment', 'kurye_odeme', 'kurye_ödeme'],
            'extra_income' => ['extra_income', 'ekstra_gelir'],
            'extra_expense' => ['extra_expense', 'ekstra_gider'],
            'deduction' => ['deduction', 'kesinti'],
            'description' => ['description', 'aciklama', 'açıklama'],
            'status' => ['status', 'durum'],
        ];

        $map = [];

        foreach ($headers as $index => $header) {
            $normalized = mb_strtolower(trim(str_replace([' ', '-'], '_', $header)));

            foreach ($aliases as $field => $names) {
                if (in_array($normalized, $names, true)) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     * @return array<string, mixed>
     */
    private function mapRow(array $row, array $map): array
    {
        $get = function (string $field) use ($row, $map) {
            if (! isset($map[$field])) {
                return null;
            }

            $value = $row[$map[$field]] ?? null;

            if ($value === null || $value === '') {
                return null;
            }

            return is_string($value) ? trim($value) : $value;
        };

        $pricingModel = $this->normalizePricingModel((string) ($get('pricing_model') ?? 'per_package'));
        $status = $this->normalizeStatus((string) ($get('status') ?? 'draft'));

        return [
            'business_id' => $get('business_id'),
            'courier_id' => $get('courier_id'),
            'period_month' => $get('period_month'),
            'period_year' => $get('period_year'),
            'pricing_model' => $pricingModel,
            'package_count' => $get('package_count') ?? 0,
            'revenue_unit_price' => $get('revenue_unit_price') ?? 0,
            'courier_unit_price' => $get('courier_unit_price') ?? 0,
            'revenue_total' => $get('revenue_total') ?? 0,
            'courier_payment' => $get('courier_payment') ?? 0,
            'extra_income' => $get('extra_income') ?? 0,
            'extra_expense' => $get('extra_expense') ?? 0,
            'deduction' => $get('deduction') ?? 0,
            'description' => $get('description'),
            'status' => $status,
        ];
    }

    private function normalizePricingModel(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'paket', 'paket_basi', 'paket_başı', 'per_package' => 'per_package',
            'aylik', 'aylık', 'aylik_sabit', 'aylık_sabit', 'monthly_fixed', 'fixed' => 'monthly_fixed',
            'saatlik', 'hourly' => 'hourly',
            'gunluk', 'günlük', 'daily' => 'daily',
            default => $value,
        };
    }

    private function normalizeStatus(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'taslak', 'draft' => 'draft',
            'bekliyor', 'pending', 'pending_review' => 'pending',
            'onaylandi', 'onaylandı', 'approved' => 'approved',
            'odendi', 'ödendi', 'paid' => 'paid',
            'iptal', 'cancelled', 'canceled' => 'cancelled',
            default => $value !== '' ? $value : 'draft',
        };
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
