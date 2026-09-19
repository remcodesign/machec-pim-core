<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

function createStockProduct(string $sku, int $stock = 10, int $priceCents = 1250): Product
{
    $category = Category::firstOrCreate(
        ['slug' => 'stock-test-category'],
        ['name' => 'Stock Test Category', 'filterable_attributes' => []],
    );

    return Product::create([
        'sku' => $sku,
        'category_id' => $category->id,
        'name' => "Product {$sku}",
        'brand' => 'Test Brand',
        'price_cents' => $priceCents,
        'stock' => $stock,
        'status' => 'published',
        'attributes' => [],
    ]);
}

function authenticateStockMovementCaller(array $abilities = ['stock:movements:write']): void
{
    Sanctum::actingAs(User::factory()->create(), $abilities);
}

test('applies a negative-quantity movement across multiple sku lines atomically and records one ledger row per sku', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-a', 10);
    createStockProduct('sku-b', 6);

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'movement-1',
        'lines' => [
            ['sku' => 'sku-a', 'quantity' => -3],
            ['sku' => 'sku-b', 'quantity' => -2],
        ],
    ]);

    $response->assertOk()
        ->assertJson([
            'applied' => true,
            'lines' => [
                ['sku' => 'sku-a', 'quantity_applied' => -3, 'resulting_stock' => 7, 'price_cents' => 1250, 'name' => 'Product sku-a'],
                ['sku' => 'sku-b', 'quantity_applied' => -2, 'resulting_stock' => 4, 'price_cents' => 1250, 'name' => 'Product sku-b'],
            ],
        ]);

    expect(Product::whereIn('sku', ['sku-a', 'sku-b'])->pluck('stock', 'sku')->all())
        ->toBe(['sku-a' => 7, 'sku-b' => 4])
        ->and(StockLedgerEntry::where('reference', 'movement-1')->count())->toBe(2);
});

test('applies a positive-quantity credit without a stock-sufficiency check', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-credit', 0);

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'credit-1',
        'lines' => [['sku' => 'sku-credit', 'quantity' => 5]],
    ]);

    $response->assertOk()
        ->assertJsonFragment(['sku' => 'sku-credit', 'quantity_applied' => 5, 'resulting_stock' => 5]);
});

test('a retried call with the same reference and sku returns the original result without applying a second movement', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-retry', 10);
    $payload = ['reference' => 'retry-1', 'lines' => [['sku' => 'sku-retry', 'quantity' => -4]]];

    $firstResponse = $this->postJson('/api/v1/stock/movements', $payload);
    $secondResponse = $this->postJson('/api/v1/stock/movements', $payload);

    $secondResponse->assertOk()
        ->assertJson($firstResponse->json());

    expect(Product::where('sku', 'sku-retry')->value('stock'))->toBe(6)
        ->and(StockLedgerEntry::where('reference', 'retry-1')->count())->toBe(1);
});

test('rejects reusing a reference with a different movement payload', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-conflict', 10);
    $payload = ['reference' => 'conflict-1', 'lines' => [['sku' => 'sku-conflict', 'quantity' => -4]]];

    $this->postJson('/api/v1/stock/movements', $payload)->assertOk();

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'conflict-1',
        'lines' => [['sku' => 'sku-conflict', 'quantity' => -2]],
    ]);

    $response->assertStatus(409)
        ->assertJson(['reason' => 'REFERENCE_CONFLICT']);

    expect(Product::where('sku', 'sku-conflict')->value('stock'))->toBe(6)
        ->and(StockLedgerEntry::where('reference', 'conflict-1')->count())->toBe(1);
});

test('a retried multi-line call with lines reordered still returns the original result without applying a second movement', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-reorder-a', 10);
    createStockProduct('sku-reorder-b', 6);

    $this->postJson('/api/v1/stock/movements', [
        'reference' => 'reorder-1',
        'lines' => [
            ['sku' => 'sku-reorder-a', 'quantity' => -3],
            ['sku' => 'sku-reorder-b', 'quantity' => -2],
        ],
    ])->assertOk();

    $secondResponse = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'reorder-1',
        'lines' => [
            ['sku' => 'sku-reorder-b', 'quantity' => -2],
            ['sku' => 'sku-reorder-a', 'quantity' => -3],
        ],
    ]);

    $secondResponse->assertOk()
        ->assertJson(['applied' => true]);

    expect(Product::whereIn('sku', ['sku-reorder-a', 'sku-reorder-b'])->pluck('stock', 'sku')->all())
        ->toBe(['sku-reorder-a' => 7, 'sku-reorder-b' => 4])
        ->and(StockLedgerEntry::where('reference', 'reorder-1')->count())->toBe(2);
});

test('resolves to the winning ledger row instead of erroring when another connection commits the same reference first', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-race', 10);

    $reference = 'race-1';

    // Simulate a concurrent request winning the race: as soon as our own
    // transaction tries to insert its ledger row, a second, independent
    // connection commits the same (reference, sku) row first, so our own
    // insert hits the unique(reference, sku) constraint.
    config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);

    $inserted = false;

    StockLedgerEntry::creating(function (StockLedgerEntry $entry) use ($reference, &$inserted): void {
        if ($entry->reference !== $reference || $inserted) {
            return;
        }

        $inserted = true;

        DB::connection('pgsql_race')->table('pim_stock_ledger')->insert([
            'reference' => $reference,
            'sku' => $entry->sku,
            'quantity' => $entry->quantity,
            'resulting_stock' => $entry->resulting_stock,
            'created_at' => now(),
        ]);
    });

    try {
        $response = $this->postJson('/api/v1/stock/movements', [
            'reference' => $reference,
            'lines' => [['sku' => 'sku-race', 'quantity' => -4]],
        ]);

        $response->assertOk()
            ->assertJson([
                'applied' => true,
                'lines' => [['sku' => 'sku-race', 'quantity_applied' => -4, 'resulting_stock' => 6]],
            ]);

        expect(StockLedgerEntry::where('reference', $reference)->count())->toBe(1);
    } finally {
        StockLedgerEntry::flushEventListeners();
        DB::connection('pgsql_race')->table('pim_stock_ledger')->where('reference', $reference)->delete();
        DB::purge('pgsql_race');
    }
});

test('rejects reusing a reference with only part of the original movement lines', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-first', 10);
    createStockProduct('sku-second', 10);
    $payload = [
        'reference' => 'partial-conflict-1',
        'lines' => [
            ['sku' => 'sku-first', 'quantity' => -1],
            ['sku' => 'sku-second', 'quantity' => -2],
        ],
    ];

    $this->postJson('/api/v1/stock/movements', $payload)->assertOk();

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'partial-conflict-1',
        'lines' => [['sku' => 'sku-first', 'quantity' => -1]],
    ]);

    $response->assertStatus(409)
        ->assertJson(['reason' => 'REFERENCE_CONFLICT']);
});

test('the response includes each line current price and name alongside its resulting stock', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-details', 3, 1999);

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'details-1',
        'lines' => [['sku' => 'sku-details', 'quantity' => -1]],
    ]);

    $response->assertOk()
        ->assertJsonFragment([
            'sku' => 'sku-details',
            'quantity_applied' => -1,
            'resulting_stock' => 2,
            'price_cents' => 1999,
            'name' => 'Product sku-details',
        ]);
});

test('rolls back the whole movement when any negative-quantity line would take stock below zero', function (): void {
    authenticateStockMovementCaller();
    createStockProduct('sku-enough', 10);
    createStockProduct('sku-empty', 1);

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'rollback-1',
        'lines' => [
            ['sku' => 'sku-enough', 'quantity' => -2],
            ['sku' => 'sku-empty', 'quantity' => -2],
        ],
    ]);

    $response->assertStatus(409)
        ->assertJson(['reason' => 'STOCK_UNAVAILABLE']);

    expect(Product::whereIn('sku', ['sku-enough', 'sku-empty'])->pluck('stock', 'sku')->all())
        ->toBe(['sku-enough' => 10, 'sku-empty' => 1])
        ->and(StockLedgerEntry::where('reference', 'rollback-1')->exists())->toBeFalse();
});

test('rejects a movement referencing a sku that does not exist', function (): void {
    authenticateStockMovementCaller();

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'unknown-sku-1',
        'lines' => [['sku' => 'sku-does-not-exist', 'quantity' => -1]],
    ]);

    $response->assertStatus(409)
        ->assertJson(['reason' => 'STOCK_UNAVAILABLE']);

    expect(StockLedgerEntry::where('reference', 'unknown-sku-1')->exists())->toBeFalse();
});

test('rejects a movement call whose token lacks the stock movements write ability', function (): void {
    authenticateStockMovementCaller(['some:other-ability']);

    $response = $this->postJson('/api/v1/stock/movements', [
        'reference' => 'forbidden-1',
        'lines' => [['sku' => 'sku-forbidden', 'quantity' => -1]],
    ]);

    $response->assertForbidden();
});
