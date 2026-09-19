<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Demo/seed catalog for pim-catalog (Domain 3, D67) — kept as plain PHP
     * arrays (not the shared `docs_local/pim-seed-catalog.json`, D67's
     * original source) so a deployed environment can run this seeder
     * without that dev-only, host-symlinked file being present at all.
     * `filters` keys become each category's own `filterable_attributes`
     * (Step 3.1), the server-side source of truth for the storefront's
     * `filterSchema.ts` (D68) — the taxonomy itself is duplicated here
     * verbatim from that JSON file, not derived from it.
     *
     * @var list<array{slug: string, name: string, filters: array<string, list<string>>}>
     */
    public const array CATEGORIES = [
        [
            'slug' => 'groepenkast-componenten',
            'name' => 'Groepenkast Componenten',
            'filters' => [
                'component_type' => ['Aardlekschakelaar', 'Installatieautomaat', 'Hoofdschakelaar'],
                'amperage' => ['16A', '40A', '63A'],
            ],
        ],
        [
            'slug' => 'schakelmateriaal',
            'name' => 'Schakelmateriaal',
            'filters' => [
                'insert_type' => ['Wandcontactdoos', 'Schakelaar', 'Dimmer'],
                'mounting' => ['Inbouw', 'Opbouw'],
            ],
        ],
        [
            'slug' => 'kabels-draden',
            'name' => 'Kabels & Draden',
            'filters' => [
                'cable_type' => ['VD-draad', 'XMvK-kabel', 'YMvK-kabel'],
                'cores_and_thickness' => ['1x2.5 mm²', '2x1.5 mm²', '3G2.5 mm²', '5G2.5 mm²'],
            ],
        ],
        [
            'slug' => 'installatiemateriaal',
            'name' => 'Installatiemateriaal',
            'filters' => [
                'material_type' => ['Lasdoppen', 'Inbouwdozen', 'Buizen'],
            ],
        ],
    ];

    /**
     * @var list<array{sku: string, name: string, brand: string, price_cents: int, category_slug: string, status: string, attributes: array<string, string>}>
     */
    public const array PRODUCTS = [
        [
            'sku' => 'prod_01',
            'name' => 'MMAT Installatieautomaat 1-polig+N 16A B-karakteristiek',
            'brand' => 'MMAT',
            'price_cents' => 895,
            'category_slug' => 'groepenkast-componenten',
            'status' => 'published',
            'attributes' => ['component_type' => 'Installatieautomaat', 'amperage' => '16A'],
        ],
        [
            'sku' => 'prod_02',
            'name' => 'ABB Aardlekschakelaar 2-polig 40A 30mA',
            'brand' => 'ABB',
            'price_cents' => 2495,
            'category_slug' => 'groepenkast-componenten',
            'status' => 'published',
            'attributes' => ['component_type' => 'Aardlekschakelaar', 'amperage' => '40A'],
        ],
        [
            'sku' => 'prod_03',
            'name' => 'MMAT Hoofdschakelaar 2-polig 40A',
            'brand' => 'MMAT',
            'price_cents' => 1895,
            'category_slug' => 'groepenkast-componenten',
            'status' => 'published',
            'attributes' => ['component_type' => 'Hoofdschakelaar', 'amperage' => '40A'],
        ],
        [
            'sku' => 'prod_04',
            'name' => 'Gira Basiselement Wandcontactdoos met randaarde',
            'brand' => 'Gira',
            'price_cents' => 645,
            'category_slug' => 'schakelmateriaal',
            'status' => 'published',
            'attributes' => ['insert_type' => 'Wandcontactdoos', 'mounting' => 'Inbouw'],
        ],
        [
            'sku' => 'prod_05',
            'name' => 'Busch-Jaeger Wisselschakelaar basiselement',
            'brand' => 'Busch-Jaeger',
            'price_cents' => 595,
            'category_slug' => 'schakelmateriaal',
            'status' => 'published',
            'attributes' => ['insert_type' => 'Schakelaar', 'mounting' => 'Inbouw'],
        ],
        [
            'sku' => 'prod_06',
            'name' => 'Gira LED Tastdimmer basiselement',
            'brand' => 'Gira',
            'price_cents' => 3995,
            'category_slug' => 'schakelmateriaal',
            'status' => 'published',
            'attributes' => ['insert_type' => 'Dimmer', 'mounting' => 'Inbouw'],
        ],
        [
            'sku' => 'prod_07',
            'name' => 'Nexans YMvK-mb Installatiekabel 3G2.5 mm² (100 meter)',
            'brand' => 'Nexans',
            'price_cents' => 8995,
            'category_slug' => 'kabels-draden',
            'status' => 'published',
            'attributes' => ['cable_type' => 'YMvK-kabel', 'cores_and_thickness' => '3G2.5 mm²'],
        ],
        [
            'sku' => 'prod_08',
            'name' => 'Donné VD-draad Bruin 2.5 mm² (100 meter ring)',
            'brand' => 'Donné',
            'price_cents' => 4495,
            'category_slug' => 'kabels-draden',
            'status' => 'published',
            'attributes' => ['cable_type' => 'VD-draad', 'cores_and_thickness' => '1x2.5 mm²'],
        ],
        [
            'sku' => 'prod_09',
            'name' => 'Nexans XMvK Buitenkabel 2x1.5 mm² (50 meter)',
            'brand' => 'Nexans',
            'price_cents' => 5995,
            'category_slug' => 'kabels-draden',
            'status' => 'published',
            'attributes' => ['cable_type' => 'XMvK-kabel', 'cores_and_thickness' => '2x1.5 mm²'],
        ],
        [
            'sku' => 'prod_10',
            'name' => 'Wago 221-413 Hersluitbare Lasklem 3-voudig (50 stuks)',
            'brand' => 'Wago',
            'price_cents' => 1295,
            'category_slug' => 'installatiemateriaal',
            'status' => 'published',
            'attributes' => ['material_type' => 'Lasdoppen'],
        ],
        [
            'sku' => 'prod_11',
            'name' => 'Attema U50 Inbouwdoos voor holle wand',
            'brand' => 'Attema',
            'price_cents' => 195,
            'category_slug' => 'installatiemateriaal',
            'status' => 'published',
            'attributes' => ['material_type' => 'Inbouwdozen'],
        ],
        [
            'sku' => 'prod_12',
            'name' => 'Pipelife Flexbuis met trekveer 16mm (100 meter)',
            'brand' => 'Pipelife',
            'price_cents' => 4295,
            'category_slug' => 'installatiemateriaal',
            'status' => 'published',
            'attributes' => ['material_type' => 'Buizen'],
        ],
    ];

    /**
     * Seed the demo catalog (D67).
     */
    public function run(): void
    {
        $categoryIdsBySlug = [];

        foreach (self::CATEGORIES as $category) {
            $categoryIdsBySlug[$category['slug']] = Category::create([
                'slug' => $category['slug'],
                'name' => $category['name'],
                'filterable_attributes' => array_keys($category['filters']),
            ])->id;
        }

        foreach (self::PRODUCTS as $product) {
            Product::create([
                'sku' => $product['sku'],
                'category_id' => $categoryIdsBySlug[$product['category_slug']],
                'name' => $product['name'],
                'brand' => $product['brand'],
                'price_cents' => $product['price_cents'],
                'status' => $product['status'],
                'attributes' => $product['attributes'],
            ]);
        }
    }
}
