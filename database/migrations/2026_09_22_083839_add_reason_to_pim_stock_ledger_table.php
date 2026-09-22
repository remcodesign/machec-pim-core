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
        Schema::table('pim_stock_ledger', function (Blueprint $table) {
            $table->string('reason')->default('api_order');
        });

        DB::statement("ALTER TABLE pim_stock_ledger ADD CONSTRAINT pim_stock_ledger_reason_check CHECK (reason IN ('purchase', 'count_correction', 'damage', 'return', 'api_order', 'other'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE pim_stock_ledger DROP CONSTRAINT pim_stock_ledger_reason_check');

        Schema::table('pim_stock_ledger', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
