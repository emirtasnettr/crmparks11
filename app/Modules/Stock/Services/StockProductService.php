<?php

namespace App\Modules\Stock\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Courier\Models\Courier;
use App\Modules\Stock\Models\StockAssignment;
use App\Modules\Stock\Models\StockProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockProductService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, StockProduct>
     */
    public function filter(array $filters): Collection
    {
        $query = StockProduct::query()->withSum([
            'assignments as assigned_quantity' => fn ($q) => $q->where('status', 'assigned'),
        ], 'quantity');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $status = $filters['status'] ?? 'all';
        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_products: int, total_quantity: int, low_stock: int, assigned_items: int}
     */
    public function summary(array $filters): array
    {
        $products = $this->filter($filters);

        return [
            'total_products' => $products->count(),
            'total_quantity' => (int) $products->sum('quantity'),
            'low_stock' => $products->filter(fn (StockProduct $p) => $p->quantity === 0)->count(),
            'assigned_items' => (int) StockAssignment::query()->where('status', 'assigned')->sum('quantity'),
        ];
    }

    public function find(int $id): ?StockProduct
    {
        return StockProduct::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): StockProduct
    {
        $product = StockProduct::query()->create([
            'name' => $data['name'],
            'sku' => $data['sku'] ?: null,
            'description' => $data['description'] ?? null,
            'quantity' => (int) $data['quantity'],
            'unit' => $data['unit'] ?? 'adet',
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        $this->activityLog->log(
            'stock_product_created',
            $product,
            newValues: [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $product->quantity,
                'unit' => $product->unit,
                'status' => $product->status,
            ],
            description: "{$product->name} ürün kartı oluşturuldu. Başlangıç stok: {$product->quantity} {$product->unit}.",
        );

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StockProduct $product, array $data): StockProduct
    {
        $oldValues = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'quantity' => $product->quantity,
            'unit' => $product->unit,
            'status' => $product->status,
            'notes' => $product->notes,
        ];

        $oldQuantity = (int) $product->quantity;
        $newQuantity = (int) $data['quantity'];

        $product->update([
            'name' => $data['name'],
            'sku' => $data['sku'] ?: null,
            'description' => $data['description'] ?? null,
            'quantity' => $newQuantity,
            'unit' => $data['unit'] ?? $product->unit,
            'status' => $data['status'] ?? $product->status,
            'notes' => $data['notes'] ?? null,
        ]);

        $product->refresh();

        $newValues = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'quantity' => $product->quantity,
            'unit' => $product->unit,
            'status' => $product->status,
            'notes' => $product->notes,
            'quantity_delta' => $newQuantity - $oldQuantity,
        ];

        if ($newQuantity > $oldQuantity) {
            $delta = $newQuantity - $oldQuantity;
            $this->activityLog->log(
                'stock_quantity_increased',
                $product,
                oldValues: $oldValues,
                newValues: $newValues,
                description: "{$product->name} stoku artırıldı: {$oldQuantity} → {$newQuantity} {$product->unit} (+{$delta}).",
            );
        } elseif ($newQuantity < $oldQuantity) {
            $delta = $oldQuantity - $newQuantity;
            $this->activityLog->log(
                'stock_quantity_decreased',
                $product,
                oldValues: $oldValues,
                newValues: $newValues,
                description: "{$product->name} stoku düşürüldü: {$oldQuantity} → {$newQuantity} {$product->unit} (-{$delta}).",
            );
        } else {
            $this->activityLog->log(
                'stock_product_updated',
                $product,
                oldValues: $oldValues,
                newValues: $newValues,
                description: "{$product->name} ürün kartı güncellendi.",
            );
        }

        return $product;
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function courierOptions(): Collection
    {
        return Courier::query()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Courier $c) => ['id' => $c->id, 'label' => $c->full_name]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(StockProduct $product, array $data, User $user): StockAssignment
    {
        $quantity = (int) $data['quantity'];

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Zimmet adedi en az 1 olmalıdır.',
            ]);
        }

        $assignment = DB::transaction(function () use ($product, $data, $user, $quantity) {
            $locked = StockProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($locked->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Yetersiz stok. Mevcut: {$locked->quantity} {$locked->unit}.",
                ]);
            }

            $quantityBefore = (int) $locked->quantity;
            $locked->decrement('quantity', $quantity);

            $created = StockAssignment::query()->create([
                'stock_product_id' => $locked->id,
                'courier_id' => (int) $data['courier_id'],
                'quantity' => $quantity,
                'assigned_at' => $data['assigned_at'] ?? now()->toDateString(),
                'status' => 'assigned',
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $user->id,
            ]);

            return [$created, $quantityBefore, $locked->fresh()];
        });

        /** @var StockAssignment $created */
        /** @var int $quantityBefore */
        /** @var StockProduct $lockedProduct */
        [$created, $quantityBefore, $lockedProduct] = $assignment;
        $created->load(['courier', 'product']);

        $courierName = $created->courier?->full_name ?? 'Kurye';
        $productName = $lockedProduct->name;
        $quantityAfter = (int) $lockedProduct->quantity;

        $this->activityLog->log(
            'stock_assigned',
            $created,
            oldValues: [
                'product_id' => $lockedProduct->id,
                'product_name' => $productName,
                'quantity' => $quantityBefore,
            ],
            newValues: [
                'product_id' => $lockedProduct->id,
                'product_name' => $productName,
                'courier_id' => $created->courier_id,
                'courier_name' => $courierName,
                'assignment_id' => $created->id,
                'assigned_quantity' => $quantity,
                'quantity' => $quantityAfter,
                'quantity_delta' => -$quantity,
            ],
            description: "{$productName}: {$quantity} {$lockedProduct->unit} {$courierName} kuryesine zimmetlendi. Stok: {$quantityBefore} → {$quantityAfter}.",
        );

        return $created;
    }

    public function returnAssignment(StockAssignment $assignment, User $user): StockAssignment
    {
        if (! $assignment->isAssigned()) {
            throw ValidationException::withMessages([
                'assignment' => 'Bu zimmet zaten iade edilmiş.',
            ]);
        }

        $result = DB::transaction(function () use ($assignment, $user) {
            $lockedAssignment = StockAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            $product = StockProduct::query()->whereKey($lockedAssignment->stock_product_id)->lockForUpdate()->firstOrFail();

            $quantityBefore = (int) $product->quantity;
            $product->increment('quantity', $lockedAssignment->quantity);

            $lockedAssignment->update([
                'status' => 'returned',
                'returned_at' => now()->toDateString(),
                'returned_by' => $user->id,
            ]);

            return [$lockedAssignment->fresh(), $product->fresh(), $quantityBefore];
        });

        /** @var StockAssignment $returned */
        /** @var StockProduct $product */
        /** @var int $quantityBefore */
        [$returned, $product, $quantityBefore] = $result;
        $returned->load(['courier', 'product']);

        $courierName = $returned->courier?->full_name ?? 'Kurye';
        $quantityAfter = (int) $product->quantity;
        $qty = (int) $returned->quantity;

        $this->activityLog->log(
            'stock_returned',
            $returned,
            oldValues: [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantityBefore,
                'courier_name' => $courierName,
            ],
            newValues: [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'courier_id' => $returned->courier_id,
                'courier_name' => $courierName,
                'assignment_id' => $returned->id,
                'returned_quantity' => $qty,
                'quantity' => $quantityAfter,
                'quantity_delta' => $qty,
            ],
            description: "{$product->name}: {$qty} {$product->unit} {$courierName} kuryesinden iade alındı. Stok: {$quantityBefore} → {$quantityAfter}.",
        );

        return $returned;
    }
}
