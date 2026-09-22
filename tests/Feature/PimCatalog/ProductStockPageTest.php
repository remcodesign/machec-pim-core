<?php

use App\Livewire\PimCatalog\ProductStock;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate('pim_admin');
});

function createStockPageProduct(string $sku = 'STOCK-001', int $stock = 10): Product
{
    $category = Category::firstOrCreate(
        ['slug' => 'stock-page-category'],
        ['name' => 'Stock Page Category', 'filterable_attributes' => []],
    );

    return Product::create([
        'sku' => $sku,
        'category_id' => $category->id,
        'name' => 'Stock Test Product',
        'brand' => 'Test Brand',
        'price_cents' => 1000,
        'stock' => $stock,
        'status' => 'published',
        'attributes' => [],
    ]);
}

test('adjust mode increases stock and writes one ledger row with the chosen reason and a self-descriptive reference', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 10);

    Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->set('stockForm.mode', 'adjust')
        ->set('stockForm.amount', 40)
        ->set('stockForm.reason', 'purchase')
        ->call('recordStockMovement')
        ->assertHasNoErrors();

    $product->refresh();
    $entry = StockLedgerEntry::where('sku', $product->sku)->sole();

    expect($product->stock)->toBe(50)
        ->and($entry->quantity)->toBe(40)
        ->and($entry->reason)->toBe('purchase')
        ->and($entry->reference)->toStartWith('admin:purchase:');
});

test('adjust mode rejects an amount that would take stock below zero with a specific error and applies nothing', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 12);

    Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->set('stockForm.mode', 'adjust')
        ->set('stockForm.amount', -40)
        ->set('stockForm.reason', 'damage')
        ->call('recordStockMovement')
        ->assertHasErrors(['stockForm.amount' => 'Can\'t remove 40 — only 12 in stock.']);

    $product->refresh();

    expect($product->stock)->toBe(12)
        ->and(StockLedgerEntry::where('sku', $product->sku)->exists())->toBeFalse();
});

test('set mode computes the correct upward delta from the counted total', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 10);

    Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->set('stockForm.mode', 'set')
        ->set('stockForm.amount', 25)
        ->set('stockForm.reason', 'count_correction')
        ->call('recordStockMovement')
        ->assertHasNoErrors();

    $product->refresh();
    $entry = StockLedgerEntry::where('sku', $product->sku)->sole();

    expect($product->stock)->toBe(25)
        ->and($entry->quantity)->toBe(15);
});

test('set mode computes the correct downward delta from the counted total', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 20);

    Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->set('stockForm.mode', 'set')
        ->set('stockForm.amount', 6)
        ->set('stockForm.reason', 'count_correction')
        ->call('recordStockMovement')
        ->assertHasNoErrors();

    $product->refresh();
    $entry = StockLedgerEntry::where('sku', $product->sku)->sole();

    expect($product->stock)->toBe(6)
        ->and($entry->quantity)->toBe(-14);
});

test('an amount that would leave stock unchanged is rejected and writes nothing', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 10);

    Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->set('stockForm.mode', 'set')
        ->set('stockForm.amount', 10)
        ->set('stockForm.reason', 'count_correction')
        ->call('recordStockMovement')
        ->assertHasErrors(['stockForm.amount']);

    $product->refresh();

    expect($product->stock)->toBe(10)
        ->and(StockLedgerEntry::where('sku', $product->sku)->exists())->toBeFalse();
});

test('a successful adjustment writes an audit log row attributed to the acting admin', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 10);

    Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->set('stockForm.mode', 'adjust')
        ->set('stockForm.amount', 5)
        ->set('stockForm.reason', 'purchase')
        ->call('recordStockMovement')
        ->assertHasNoErrors();

    expect(AuditLog::query()
        ->where('user_id', $admin->id)
        ->where('action', 'product.stock_adjusted')
        ->where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->exists())->toBeTrue();
});

test('a rejected adjustment writes no audit log row', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 5);

    Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->set('stockForm.mode', 'adjust')
        ->set('stockForm.amount', -50)
        ->set('stockForm.reason', 'damage')
        ->call('recordStockMovement');

    expect(AuditLog::where('action', 'product.stock_adjusted')->exists())->toBeFalse();
});

test('the ledger only lists entries for this product own sku', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct('own-sku', 10);
    $otherProduct = createStockPageProduct('other-sku', 10);

    StockLedgerEntry::factory()->create(['sku' => $product->sku, 'reference' => 'ref-own']);
    StockLedgerEntry::factory()->create(['sku' => $otherProduct->sku, 'reference' => 'ref-other']);

    $entries = Livewire::actingAs($admin)
        ->test(ProductStock::class, ['product' => $product])
        ->instance()
        ->entries();

    expect($entries->pluck('reference')->all())->toBe(['ref-own']);
});

test('the ledger paginates and filters by reason', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $product = createStockPageProduct(stock: 10);

    StockLedgerEntry::factory()->count(16)->sequence(fn ($sequence) => [
        'sku' => $product->sku,
        'reference' => "ref-{$sequence->index}",
        'reason' => 'purchase',
    ])->create();

    StockLedgerEntry::factory()->create([
        'sku' => $product->sku,
        'reference' => 'ref-damage',
        'reason' => 'damage',
    ]);

    $component = Livewire::actingAs($admin)->test(ProductStock::class, ['product' => $product]);

    expect($component->instance()->entries()->total())->toBe(17)
        ->and($component->instance()->entries()->count())->toBe(15);

    $component->set('reasonFilter', 'damage');

    expect($component->instance()->entries()->total())->toBe(1)
        ->and($component->instance()->entries()->first()->reference)->toBe('ref-damage');
});

test('a request with no reason on the stock-movements API persists the api_order default', function (): void {
    $product = createStockPageProduct(stock: 10);

    Sanctum::actingAs(User::factory()->create(), ['stock:movements:write']);

    $this->postJson('/api/v1/stock/movements', [
        'reference' => 'no-reason-1',
        'lines' => [['sku' => $product->sku, 'quantity' => -1]],
    ])->assertOk();

    expect(StockLedgerEntry::where('reference', 'no-reason-1')->value('reason'))->toBe('api_order');
});

test('a non pim_admin cannot open the product stock page', function (): void {
    $user = User::factory()->create();
    $product = createStockPageProduct(stock: 10);

    $this->actingAs($user)->get(route('products.stock', $product))->assertForbidden();
});
