<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pim:touch-products {--count=5} {--all}')]
#[Description('Mark products as modified by updating their timestamps')]
class TouchProductsCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('all')) {
            // Update all products' updated_at timestamp
            $touchedCount = Product::query()->update(['updated_at' => now()]);
        } else {
            $productIds = Product::query()
                ->inRandomOrder()
                ->limit(max(0, (int) $this->option('count')))
                ->pluck('id');

            $touchedCount = Product::query()
                ->whereKey($productIds)
                ->update(['updated_at' => now()]);
        }

        $this->info("Touched {$touchedCount} product(s).");

        return self::SUCCESS;
    }
}
