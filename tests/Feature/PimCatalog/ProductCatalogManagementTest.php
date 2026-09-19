<?php

use App\Livewire\PimCatalog\AdminDashboard;
use App\Livewire\PimCatalog\CategoryForm;
use App\Livewire\PimCatalog\CategoryIndex;
use App\Livewire\PimCatalog\ProductForm;
use App\Livewire\PimCatalog\ProductIndex;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate('pim_admin');
});

test('pim_admin creates a product through the Livewire form and it appears in the index', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create([
        'slug' => 'schakelmateriaal',
        'name' => 'Schakelmateriaal',
        'filterable_attributes' => ['insert_type'],
    ]);

    Livewire::actingAs($admin)
        ->test(ProductForm::class)
        ->set('form.sku', 'GIRA-001')
        ->set('form.name', 'Gira Dimmer')
        ->set('form.brand', 'Gira')
        ->set('form.price', '24.50')
        ->set('form.category_slug', $category->slug)
        ->set('form.status', 'published')
        ->set('form.attributes.insert_type', 'Dimmer')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('sku', 'GIRA-001')->sole();

    expect($product->name)->toBe('Gira Dimmer')
        ->and($product->price_cents)->toBe(2450)
        ->and($product->status->value)->toBe('published')
        ->and($product->attributes)->toBe(['insert_type' => 'Dimmer'])
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'product.created')
            ->where('subject_type', Product::class)
            ->where('subject_id', $product->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(ProductIndex::class)
        ->assertSee('Gira Dimmer');
});

test('pim_admin edits an existing product through the Livewire form', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'kabels', 'name' => 'Kabels', 'filterable_attributes' => []]);
    $product = Product::create([
        'sku' => 'OLD-SKU',
        'category_id' => $category->id,
        'name' => 'Old Name',
        'brand' => 'OldBrand',
        'price_cents' => 1000,
        'status' => 'draft',
        'attributes' => [],
    ]);

    Livewire::actingAs($admin)
        ->test(ProductForm::class, ['product' => $product])
        ->set('form.name', 'New Name')
        ->set('form.status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $product->refresh();

    expect($product->name)->toBe('New Name')
        ->and($product->status->value)->toBe('published')
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'product.updated')
            ->where('subject_type', Product::class)
            ->where('subject_id', $product->id)->exists())->toBeTrue();
});

test('ProductIndex filters/sorts/paginates through the same ListProductsAction the storefront read API uses (D111)', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $matching = Category::create(['slug' => 'matching', 'name' => 'Matching', 'filterable_attributes' => ['amperage']]);
    $other = Category::create(['slug' => 'other', 'name' => 'Other', 'filterable_attributes' => []]);

    Product::create(['sku' => 'match', 'category_id' => $matching->id, 'name' => 'Zebra', 'brand' => 'EMAT', 'price_cents' => 900, 'status' => 'published', 'attributes' => ['amperage' => '16A']]);
    Product::create(['sku' => 'wrong-brand', 'category_id' => $matching->id, 'name' => 'Apple', 'brand' => 'ABB', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '16A']]);
    Product::create(['sku' => 'wrong-category', 'category_id' => $other->id, 'name' => 'Mango', 'brand' => 'EMAT', 'price_cents' => 500, 'status' => 'published', 'attributes' => []]);

    $component = Livewire::actingAs($admin)
        ->test(ProductIndex::class)
        ->set('category', 'matching')
        ->set('brand', 'EMAT')
        ->set('attributeFilters.amperage', '16A')
        ->set('sort', 'price_desc');

    expect($component->instance()->products->pluck('sku')->all())->toBe(['match']);
});

test('ProductIndex\'s brand filter and ProductForm\'s brand field both offer the distinct brands already in use', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'brand-test', 'name' => 'Brand Test', 'filterable_attributes' => []]);

    Product::create(['sku' => 'b1', 'category_id' => $category->id, 'name' => 'B1', 'brand' => 'Gira', 'price_cents' => 100, 'status' => 'published', 'attributes' => []]);
    Product::create(['sku' => 'b2', 'category_id' => $category->id, 'name' => 'B2', 'brand' => 'ABB', 'price_cents' => 100, 'status' => 'published', 'attributes' => []]);
    Product::create(['sku' => 'b3', 'category_id' => $category->id, 'name' => 'B3', 'brand' => 'Gira', 'price_cents' => 100, 'status' => 'published', 'attributes' => []]);

    expect(Livewire::actingAs($admin)->test(ProductIndex::class)->instance()->brands()->all())
        ->toBe(['ABB', 'Gira'])
        ->and(Livewire::actingAs($admin)->test(ProductForm::class)->instance()->brands()->all())->toBe(['ABB', 'Gira']);
});

test('ProductIndex\'s price range reflects the current category/brand filters but ignores the price bounds themselves', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $matching = Category::create(['slug' => 'range-matching', 'name' => 'Range Matching', 'filterable_attributes' => []]);
    $other = Category::create(['slug' => 'range-other', 'name' => 'Range Other', 'filterable_attributes' => []]);

    Product::create(['sku' => 'r1', 'category_id' => $matching->id, 'name' => 'R1', 'brand' => 'B', 'price_cents' => 500, 'status' => 'published', 'attributes' => []]);
    Product::create(['sku' => 'r2', 'category_id' => $matching->id, 'name' => 'R2', 'brand' => 'B', 'price_cents' => 1500, 'status' => 'published', 'attributes' => []]);
    Product::create(['sku' => 'r3', 'category_id' => $other->id, 'name' => 'R3', 'brand' => 'B', 'price_cents' => 9000, 'status' => 'published', 'attributes' => []]);

    $component = Livewire::actingAs($admin)
        ->test(ProductIndex::class)
        ->set('category', 'range-matching')
        ->set('price_min', '10');

    expect($component->instance()->priceRange())->toBe(['min' => 500, 'max' => 1500]);
});

test('ProductIndex\'s status filter narrows the listing and an unrecognized status is silently ignored', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'status-test', 'name' => 'Status Test', 'filterable_attributes' => []]);

    Product::create(['sku' => 's-draft', 'category_id' => $category->id, 'name' => 'S Draft', 'brand' => 'B', 'price_cents' => 100, 'status' => 'draft', 'attributes' => []]);
    Product::create(['sku' => 's-published', 'category_id' => $category->id, 'name' => 'S Published', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => []]);

    $filtered = Livewire::actingAs($admin)->test(ProductIndex::class)->set('status', 'draft');
    expect($filtered->instance()->products->pluck('sku')->all())->toBe(['s-draft']);

    $bogus = Livewire::actingAs($admin)->test(ProductIndex::class)->set('status', 'not-a-real-status');
    expect($bogus->instance()->products->pluck('sku')->sort()->values()->all())->toBe(['s-draft', 's-published']);
});

test('clearFilters resets every ProductIndex filter back to its default and resets the page', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'clear-test', 'name' => 'Clear Test', 'filterable_attributes' => ['amperage']]);
    Product::create(['sku' => 'c1', 'category_id' => $category->id, 'name' => 'C1', 'brand' => 'Gira', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '16A']]);

    $component = Livewire::actingAs($admin)
        ->test(ProductIndex::class)
        ->set('category', 'clear-test')
        ->set('brand', 'Gira')
        ->set('status', 'published')
        ->set('price_min', '1')
        ->set('price_max', '2')
        ->set('sort', 'price_desc')
        ->set('attributeFilters.amperage', '16A');

    expect($component->instance()->hasActiveFilters())->toBeTrue();

    $component->call('clearFilters');

    expect($component->get('category'))->toBe('')
        ->and($component->get('brand'))->toBe('')
        ->and($component->get('status'))->toBe('')
        ->and($component->get('price_min'))->toBe('')
        ->and($component->get('price_max'))->toBe('')
        ->and($component->get('sort'))->toBe('name_asc')
        ->and($component->get('attributeFilters'))->toBe([])
        ->and($component->instance()->hasActiveFilters())->toBeFalse();
});

test('Category::distinctAttributeValues returns the distinct, sorted, non-empty values already used for that attribute', function (): void {
    $category = Category::create(['slug' => 'values-test', 'name' => 'Values Test', 'filterable_attributes' => ['amperage']]);
    Product::create(['sku' => 'v1', 'category_id' => $category->id, 'name' => 'V1', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '40A']]);
    Product::create(['sku' => 'v2', 'category_id' => $category->id, 'name' => 'V2', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '16A']]);
    Product::create(['sku' => 'v3', 'category_id' => $category->id, 'name' => 'V3', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '16A']]);
    Product::create(['sku' => 'v4', 'category_id' => $category->id, 'name' => 'V4', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => []]);

    expect($category->distinctAttributeValues('amperage')->all())->toBe(['16A', '40A']);
});

test('AdminDashboard shows the correct product/category/low-stock counts', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'test-cat', 'name' => 'Test Cat', 'filterable_attributes' => []]);

    Product::create(['sku' => 'p1', 'category_id' => $category->id, 'name' => 'P1', 'brand' => 'B', 'price_cents' => 100, 'stock' => 2, 'status' => 'draft', 'attributes' => []]);
    Product::create(['sku' => 'p2', 'category_id' => $category->id, 'name' => 'P2', 'brand' => 'B', 'price_cents' => 100, 'stock' => 20, 'status' => 'published', 'attributes' => []]);
    Product::create(['sku' => 'p3', 'category_id' => $category->id, 'name' => 'P3', 'brand' => 'B', 'price_cents' => 100, 'stock' => 0, 'status' => 'archived', 'attributes' => []]);

    $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

    expect($component->instance()->totalProducts)->toBe(3)
        ->and($component->instance()->totalCategories)->toBe(1)
        ->and($component->instance()->lowStockCount)->toBe(2)
        ->and($component->instance()->statusBreakdown)->toBe(['draft' => 1, 'published' => 1, 'archived' => 1]);
});

test('AdminDashboard shows recent stock movements before recent audit activity', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $auditLog = AuditLog::factory()->create([
        'user_id' => $admin->id,
        'action' => 'product.created',
        'subject_type' => Product::class,
        'subject_id' => 1,
    ]);

    $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

    expect($component->instance()->recentAuditLog->sole()->is($auditLog))->toBeTrue();

    $component
        ->assertSee('Recent stock movements')
        ->assertSee('Recent activity')
        ->assertSeeInOrder(['Recent stock movements', 'Recent activity', 'product.created']);
});

test('a customer-role user cannot mount the ProductForm or AdminDashboard Livewire component', function (): void {
    $plainUser = User::factory()->create();
    $category = Category::create(['slug' => 'guarded', 'name' => 'Guarded', 'filterable_attributes' => []]);
    $product = Product::create(['sku' => 'guarded-sku', 'category_id' => $category->id, 'name' => 'Guarded', 'brand' => 'B', 'price_cents' => 100, 'status' => 'draft', 'attributes' => []]);

    $this->actingAs($plainUser)->get(route('products.create'))->assertForbidden();
    $this->actingAs($plainUser)->get(route('dashboard'))->assertForbidden();

    Livewire::actingAs($plainUser)
        ->test(ProductForm::class, ['product' => $product])
        ->call('save')
        ->assertForbidden();
});

test('CategoryIndex lists categories with their filterable attributes and product count', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'listed', 'name' => 'Listed Category', 'filterable_attributes' => ['amperage']]);
    Product::create(['sku' => 'listed-product', 'category_id' => $category->id, 'name' => 'Listed Product', 'brand' => 'B', 'price_cents' => 100, 'status' => 'draft', 'attributes' => []]);

    Livewire::actingAs($admin)
        ->test(CategoryIndex::class)
        ->assertSee('Listed Category')
        ->assertSee('amperage')
        ->assertSee('1');
});

test('pim_admin creates and edits a category through the Livewire form', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');

    Livewire::actingAs($admin)
        ->test(CategoryForm::class)
        ->set('form.name', 'New Category')
        ->set('form.slug', 'new-category')
        ->set('form.filterable_attributes', ['amperage', 'component_type'])
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('slug', 'new-category')->sole();

    expect($category->filterable_attributes)->toBe(['amperage', 'component_type'])
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'category.created')
            ->where('subject_type', Category::class)
            ->where('subject_id', $category->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(CategoryForm::class, ['category' => $category])
        ->set('form.name', 'Renamed Category')
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();

    expect($category->name)->toBe('Renamed Category')
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'category.updated')
            ->where('subject_type', Category::class)
            ->where('subject_id', $category->id)->exists())->toBeTrue();
});

test('renaming an existing attribute line on save cascades the rename across that category\'s own products', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'rename-cat', 'name' => 'Rename Cat', 'filterable_attributes' => ['amperage', 'component_type']]);
    $inCategory = Product::create(['sku' => 'rc1', 'category_id' => $category->id, 'name' => 'RC1', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '16A', 'component_type' => 'Dimmer']]);

    $otherCategory = Category::create(['slug' => 'other-cat', 'name' => 'Other Cat', 'filterable_attributes' => ['amperage']]);
    $otherProduct = Product::create(['sku' => 'rc2', 'category_id' => $otherCategory->id, 'name' => 'RC2', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '40A']]);

    Livewire::actingAs($admin)
        ->test(CategoryForm::class, ['category' => $category])
        ->set('form.filterable_attributes.0', 'amps')
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();
    $inCategory->refresh();
    $otherProduct->refresh();

    expect($category->filterable_attributes)->toBe(['amps', 'component_type'])
        ->and($inCategory->attributes)->toBe(['amps' => '16A', 'component_type' => 'Dimmer'])
        ->and($otherProduct->attributes)->toBe(['amperage' => '40A']);
});

test('clearing an existing attribute line\'s text instead of using the delete button fails validation', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'blank-cat', 'name' => 'Blank Cat', 'filterable_attributes' => ['amperage']]);

    Livewire::actingAs($admin)
        ->test(CategoryForm::class, ['category' => $category])
        ->set('form.filterable_attributes.0', '')
        ->call('save')
        ->assertHasErrors(['form.filterable_attributes']);

    expect($category->fresh()->filterable_attributes)->toBe(['amperage']);
});

test('removeAttribute deletes an attribute from the category and strips it from that category\'s own products only', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'del-cat', 'name' => 'Del Cat', 'filterable_attributes' => ['amperage', 'component_type']]);
    $inCategory = Product::create(['sku' => 'dc1', 'category_id' => $category->id, 'name' => 'DC1', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '16A', 'component_type' => 'Dimmer']]);

    $otherCategory = Category::create(['slug' => 'del-other', 'name' => 'Del Other', 'filterable_attributes' => ['amperage']]);
    $otherProduct = Product::create(['sku' => 'dc2', 'category_id' => $otherCategory->id, 'name' => 'DC2', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '40A']]);

    Livewire::actingAs($admin)
        ->test(CategoryForm::class, ['category' => $category])
        ->call('removeAttribute', 'amperage')
        ->assertHasNoErrors();

    $category->refresh();
    $inCategory->refresh();
    $otherProduct->refresh();

    expect($category->filterable_attributes)->toBe(['component_type'])
        ->and($inCategory->attributes)->toBe(['component_type' => 'Dimmer'])
        ->and($otherProduct->attributes)->toBe(['amperage' => '40A'])
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'category.attribute_removed')
            ->where('subject_type', Category::class)
            ->where('subject_id', $category->id)->exists())->toBeTrue();
});

test('addAttributeLine appends a blank line that is a no-op on products until it is filled in and saved', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $category = Category::create(['slug' => 'add-cat', 'name' => 'Add Cat', 'filterable_attributes' => ['amperage']]);
    $product = Product::create(['sku' => 'ac1', 'category_id' => $category->id, 'name' => 'AC1', 'brand' => 'B', 'price_cents' => 100, 'status' => 'published', 'attributes' => ['amperage' => '16A']]);

    Livewire::actingAs($admin)
        ->test(CategoryForm::class, ['category' => $category])
        ->call('addAttributeLine')
        ->set('form.filterable_attributes.1', 'voltage')
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();
    $product->refresh();

    expect($category->filterable_attributes)->toBe(['amperage', 'voltage'])
        ->and($product->attributes)->toBe(['amperage' => '16A']);
});

test('a crafted attributeFilters query-string entry never crashes the page, even for an unknown attribute key', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    Category::create(['slug' => 'groepenkast-componenten', 'name' => 'Groepenkast Componenten', 'filterable_attributes' => ['amperage']]);

    $response = $this->actingAs($admin)->get(
        '/products?category=groepenkast-componenten&attributeFilters[amperage25]=',
    );

    $response->assertOk();
});
