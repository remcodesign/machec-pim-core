<?php

namespace App\Actions\PimCatalog;

use App\Data\Requests\StockMovementData;
use App\Data\Requests\StockMovementLineRequestData;
use App\Data\Responses\StockMovementLineData;
use App\Data\Responses\StockMovementResultData;
use App\Enums\StockMovementReason;
use App\Exceptions\StockMovementReferenceConflictException;
use App\Exceptions\StockUnavailableException;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordStockMovementAction
{
    public function handle(StockMovementData $data): StockMovementResultData
    {
        try {
            return DB::transaction(function () use ($data): StockMovementResultData {
                // Resolve existing ledger entries for the requested reference.
                $lines = collect($data->lines);
                $existingEntries = $this->findExistingEntries($data->reference);

                // A. Same reference and same SKU/quantity payload: return the original result.
                if ($existingEntries->isNotEmpty() && $this->movementMatches($lines, $existingEntries)) {
                    return $this->resultForExistingMovement($lines, $existingEntries);
                }

                // B. Same reference but different payload: reject with a conflict.
                if ($existingEntries->isNotEmpty()) {
                    throw new StockMovementReferenceConflictException;
                }

                // C. New reference: apply the movement.
                $products = $this->lockProducts($lines);
                $this->ensureMovementCanBeApplied($lines, $products);

                // Finally, apply new movements and assemble the response lines.
                $resultLines = $this->applyMovement($data->reference, $data->reason, $lines, $products);

                return new StockMovementResultData(applied: true, lines: $resultLines);
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent request for the same new reference committed first,
            // aborting our transaction. Resolve against what it actually wrote.
            return $this->resolveAfterConcurrentInsert($data);
        }
    }

    /**
     * Re-checks a reference that lost a race to insert its ledger rows first.
     * Mirrors the idempotency check above, but runs after the winning
     * transaction has committed instead of racing against it.
     */
    private function resolveAfterConcurrentInsert(StockMovementData $data): StockMovementResultData
    {
        $lines = collect($data->lines);
        $existingEntries = $this->findExistingEntries($data->reference);

        if ($existingEntries->isNotEmpty() && $this->movementMatches($lines, $existingEntries)) {
            return $this->resultForExistingMovement($lines, $existingEntries);
        }

        throw new StockMovementReferenceConflictException;
    }

    /**
     * @return Collection<string, StockLedgerEntry>
     */
    private function findExistingEntries(string $reference): Collection
    {
        return StockLedgerEntry::query()
            ->where('reference', $reference)
            ->get()
            ->keyBy('sku');
    }

    /**
     * @param  Collection<int, StockMovementLineRequestData>  $lines
     * @param  Collection<string, StockLedgerEntry>  $entries
     */
    private function movementMatches(Collection $lines, Collection $entries): bool
    {
        $requestedQuantities = $lines->mapWithKeys(
            fn (StockMovementLineRequestData $line): array => [$line->sku => $line->quantity],
        )->sortKeys();
        $recordedQuantities = $entries->mapWithKeys(
            fn (StockLedgerEntry $entry): array => [$entry->sku => $entry->quantity],
        )->sortKeys();

        return $requestedQuantities->all() === $recordedQuantities->all();
    }

    /**
     * @param  Collection<int, StockMovementLineRequestData>  $lines
     * @return Collection<string, Product>
     */
    private function lockProducts(Collection $lines): Collection
    {
        return Product::query()
            ->whereIn('sku', $lines->pluck('sku')->all())
            ->orderBy('sku')
            ->lockForUpdate()
            ->get()
            ->keyBy('sku');
    }

    /**
     * @param  Collection<int, StockMovementLineRequestData>  $lines
     * @param  Collection<string, Product>  $products
     */
    private function ensureMovementCanBeApplied(Collection $lines, Collection $products): void
    {
        foreach ($lines as $line) {
            $product = $products->get($line->sku);

            if (! $product instanceof Product || ($line->quantity < 0 && $product->stock + $line->quantity < 0)) {
                throw new StockUnavailableException;
            }
        }
    }

    /**
     * @param  Collection<int, StockMovementLineRequestData>  $lines
     * @param  Collection<string, Product>  $products
     * @return array<int, StockMovementLineData>
     */
    private function applyMovement(string $reference, StockMovementReason $reason, Collection $lines, Collection $products): array
    {
        $resultLines = [];

        foreach ($lines as $line) {
            $product = $products->get($line->sku);

            if (! $product instanceof Product) {
                throw new StockUnavailableException;
            }

            $resultingStock = $product->stock + $line->quantity;
            $product->update(['stock' => $resultingStock]);

            StockLedgerEntry::create([
                'reference' => $reference,
                'sku' => $line->sku,
                'quantity' => $line->quantity,
                'resulting_stock' => $resultingStock,
                'reason' => $reason->value,
            ]);

            $resultLines[] = $this->resultLine($product, $line->quantity, $resultingStock);
        }

        return $resultLines;
    }

    /**
     * @param  Collection<int, StockMovementLineRequestData>  $lines
     * @param  Collection<string, StockLedgerEntry>  $entries
     */
    private function resultForExistingMovement(Collection $lines, Collection $entries): StockMovementResultData
    {
        $products = Product::query()
            ->whereIn('sku', $lines->pluck('sku')->all())
            ->get()
            ->keyBy('sku');

        $resultLines = $lines->map(function (StockMovementLineRequestData $line) use ($entries, $products): StockMovementLineData {
            $entry = $entries->get($line->sku);
            $product = $products->get($line->sku);

            if (! $entry instanceof StockLedgerEntry || ! $product instanceof Product) {
                throw new StockUnavailableException;
            }

            return $this->resultLine($product, $entry->quantity, $entry->resulting_stock);
        })->all();

        return new StockMovementResultData(applied: true, lines: $resultLines);
    }

    private function resultLine(Product $product, int $quantity, int $resultingStock): StockMovementLineData
    {
        return new StockMovementLineData(
            sku: $product->sku,
            quantity_applied: $quantity,
            resulting_stock: $resultingStock,
            price_cents: $product->price_cents,
            name: $product->name,
        );
    }
}
