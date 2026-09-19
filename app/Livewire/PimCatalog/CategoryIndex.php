<?php

namespace App\Livewire\PimCatalog;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categories')]
class CategoryIndex extends Component
{
    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::withCount('products')->with('parent')->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.category-index');
    }
}
