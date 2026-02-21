<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;

final class KardexService
{
    /**
     * Obtiene el último registro de inventario para una variante en un almacén específico
     *
     * @return array<string, mixed>
     */
    public function getLastRecord(int $productProductId, int $warehouseId): array
    {
        $lastRecord = Inventory::where('product_product_id', $productProductId)
            ->where('warehouse_id', $warehouseId)
            ->latest()
            ->first();

        return [
            'quantity' => $lastRecord?->quantity_balance ?? 0.0,
            'cost' => $lastRecord?->cost_balance ?? 0.0,
            'total' => $lastRecord?->total_balance ?? 0.0,
            'date' => $lastRecord?->created_at ?? null,
        ];
    }

    /**
     * Registra una entrada al inventario (compras, transferencias entrantes, etc.)
     *
     * @param  mixed  $model  Modelo polimórfico (Purchase, Transfer, etc.)
     * @param  array<string, mixed>  $variant  Datos de la variante ['id', 'quantity', 'price', 'subtotal']
     */
    public function registerEntry(mixed $model, array $variant, int $warehouseId, string $detail): void
    {
        $lastRecord = $this->getLastRecord((int) $variant['id'], $warehouseId);

        $qty = (float) ($variant['quantity'] ?? 0);

        // Normalizar costo unitario: si viene "subtotal" (monto base sin IGV) y es > 0, usarlo.
        $baseSubtotal = $variant['subtotal'] ?? null;
        $unitCost = ($baseSubtotal !== null && (float) $baseSubtotal > 0 && $qty > 0)
            ? ((float) $baseSubtotal / $qty)
            : (float) ($variant['price'] ?? 0);

        // Calcular nuevo balance usando promedio ponderado
        $newQuantityBalance = $lastRecord['quantity'] + $qty;
        $newTotalBalance = $lastRecord['total'] + ($qty * $unitCost);
        $newCostBalance = $newTotalBalance / ($newQuantityBalance ?: 1);

        // Crear registro de inventario (Stock entra)
        $model->inventories()->create([
            'detail' => $detail,
            'quantity_in' => $qty,
            'cost_in' => $unitCost,
            'total_in' => $qty * $unitCost,
            'quantity_out' => 0.0,
            'cost_out' => 0.0,
            'total_out' => 0.0,
            'quantity_balance' => $newQuantityBalance,
            'cost_balance' => $newCostBalance,
            'total_balance' => $newTotalBalance,
            'product_product_id' => $variant['id'],
            'warehouse_id' => $warehouseId,
        ]);
    }

    /**
     * Registra una salida del inventario (ventas, transferencias salientes, etc.)
     *
     * @param  mixed  $model  Modelo polimórfico (Sale, Transfer, etc.)
     * @param  array<string, mixed>  $variant  Datos de la variante ['id', 'quantity']
     */
    public function registerExit(mixed $model, array $variant, int $warehouseId, string $detail): void
    {
        $lastRecord = $this->getLastRecord((int) $variant['id'], $warehouseId);

        $qty = (float) ($variant['quantity'] ?? 0);

        // Calcular nuevo balance
        $newQuantityBalance = $lastRecord['quantity'] - $qty;
        $newTotalBalance = $lastRecord['total'] - ($qty * $lastRecord['cost']);
        $newCostBalance = $newTotalBalance / ($newQuantityBalance ?: 1);

        // Crear registro de inventario (Stock sale, a costo promedio de la última entrada)
        $model->inventories()->create([
            'detail' => $detail,
            'quantity_in' => 0.0,
            'cost_in' => 0.0,
            'total_in' => 0.0,
            'quantity_out' => $qty,
            'cost_out' => $lastRecord['cost'],
            'total_out' => $qty * $lastRecord['cost'],
            'quantity_balance' => $newQuantityBalance,
            'cost_balance' => $newCostBalance,
            'total_balance' => $newTotalBalance,
            'product_product_id' => $variant['id'],
            'warehouse_id' => $warehouseId,
        ]);
    }

    /**
     * Obtiene el kardex completo de una variante en un almacén
     *
     * @return Collection<int, Inventory>
     */
    public function getKardex(int $productProductId, int $warehouseId): Collection
    {
        return Inventory::where('product_product_id', $productProductId)
            ->where('warehouse_id', $warehouseId)
            ->with(['inventoryable', 'warehouse'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Obtiene el stock actual de una variante en un almacén específico
     */
    public function getCurrentStock(int $productProductId, int $warehouseId): float
    {
        $lastRecord = $this->getLastRecord($productProductId, $warehouseId);

        return (float) $lastRecord['quantity'];
    }

    /**
     * Validar si hay suficiente stock para una salida
     */
    public function hasEnoughStock(int $productProductId, int $warehouseId, float $requiredQuantity): bool
    {
        $currentStock = $this->getCurrentStock($productProductId, $warehouseId);

        return $currentStock >= $requiredQuantity;
    }

    /**
     * Registra un ajuste de inventario (corrección de stock manual)
     *
     * @param  mixed  $model  Modelo polimórfico (Adjustment, etc.)
     * @param  array<string, mixed>  $variant  Datos de la variante ['id', 'quantity', 'reason']
     */
    public function registerAdjustment(mixed $model, array $variant, int $warehouseId, string $detail): void
    {
        $qty = (float) ($variant['quantity'] ?? 0);

        // Determinar si es ajuste positivo o negativo
        if ($qty >= 0) {
            // Ajuste positivo (entrada)
            $this->registerEntry($model, $variant, $warehouseId, $detail);
        } else {
            // Ajuste negativo (salida)
            $variant['quantity'] = abs($qty);
            $this->registerExit($model, $variant, $warehouseId, $detail);
        }
    }
}
