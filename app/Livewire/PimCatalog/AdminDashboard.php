<?php

namespace App\Livewire\PimCatalog;

use App\Enums\ProductStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * D89 — this replaces the starter kit's own placeholder `dashboard` route,
 * not a second one; D88's own local `<x-stat-tile>` + `--admin-accent` are
 * what visibly distinguish this app's admin from the three co-located
 * ones.
 */
#[Layout('layouts.app')]
#[Title('Dashboard')]
class AdminDashboard extends Component
{
    /**
     * Products at or below this stock level count as low stock.
     */
    private const int LOW_STOCK_THRESHOLD = 5;

    #[Computed]
    public function totalProducts(): int
    {
        return Product::count();
    }

    #[Computed]
    public function totalCategories(): int
    {
        return Category::count();
    }

    #[Computed]
    public function lowStockCount(): int
    {
        return Product::where('stock', '<=', self::LOW_STOCK_THRESHOLD)->count();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function statusBreakdown(): array
    {
        return collect(ProductStatus::cases())
            ->mapWithKeys(fn (ProductStatus $status): array => [
                $status->value => Product::where('status', $status)->count(),
            ])
            ->all();
    }

    /**
     * @return Collection<int, StockLedgerEntry>
     */
    #[Computed]
    public function recentStockMovements(): Collection
    {
        return StockLedgerEntry::latest('created_at')->limit(5)->get();
    }

    /**
     * @return EloquentCollection<int, AuditLog>
     */
    #[Computed]
    public function recentAuditLog(): EloquentCollection
    {
        return AuditLog::with('user')->latest('created_at')->limit(5)->get();
    }

    public function render(): View
    {
        return view('livewire.admin-dashboard');
    }
}
