<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pim_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('pim_categories')->nullOnDelete();
            $table->jsonb('filterable_attributes')->default('[]');
        });

        Schema::create('pim_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->foreignId('category_id')->constrained('pim_categories');
            $table->string('name');
            $table->string('brand');
            $table->integer('price_cents');
            $table->integer('stock')->default(0);
            $table->vector('embedding', 1536)->nullable();
            $table->jsonb('attributes')->default('{}');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pim_products ADD CONSTRAINT pim_products_stock_check CHECK (stock >= 0)');
        DB::statement("ALTER TABLE pim_products ADD CONSTRAINT pim_products_status_check CHECK (status IN ('draft', 'published', 'archived'))");

        Schema::create('pim_stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->string('sku');
            $table->integer('quantity');
            $table->integer('resulting_stock');
            $table->timestamp('created_at')->nullable();
            $table->unique(['reference', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_stock_ledger');
        Schema::dropIfExists('pim_products');
        Schema::dropIfExists('pim_categories');
    }
};
