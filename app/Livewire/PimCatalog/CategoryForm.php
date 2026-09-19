<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\RemoveCategoryAttributeAction;
use App\Actions\PimCatalog\SaveCategoryAction;
use App\Livewire\PimCatalog\Forms\CategoryDetailsForm;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Category')]
class CategoryForm extends Component
{
    public ?Category $category = null;

    public CategoryDetailsForm $form;

    public function mount(?Category $category = null): void
    {
        $this->category = $category;
        $this->form->setCategory($category);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function parentOptions(): Collection
    {
        return Category::when(
            $this->category,
            fn ($query, Category $category) => $query->whereKeyNot($category->id),
        )->orderBy('name')->get();
    }

    /**
     * Lines at this index or below already exist in the database — the
     * Blade view uses this to decide whether a line's remove button is
     * the plain, unconfirmed `removeAttributeLine()` (a blank line added
     * this session, nothing to cascade) or the confirmed, cascading
     * `removeAttribute()`.
     */
    public function persistedAttributeCount(): int
    {
        return count($this->category instanceof Category ? $this->category->filterable_attributes : []);
    }

    public function addAttributeLine(): void
    {
        $this->form->filterable_attributes[] = '';
    }

    public function removeAttributeLine(int $index): void
    {
        $lines = $this->form->filterable_attributes;
        unset($lines[$index]);

        $this->form->filterable_attributes = array_values($lines);
    }

    public function removeAttribute(RemoveCategoryAttributeAction $action, string $attributeKey): void
    {
        abort_unless($this->category instanceof Category, 404);

        /** @var User $admin */
        $admin = Auth::user();

        $this->category = $action->handle(request(), $admin, $this->category, $attributeKey);

        // Only the attribute list is refreshed — any other unsaved edit
        // in the form (name/slug/parent) is left exactly as typed.
        $this->form->filterable_attributes = $this->category->filterable_attributes;
    }

    public function save(SaveCategoryAction $action): void
    {
        $this->form->validate();

        $renames = [];
        $originalKeys = $this->category instanceof Category ? $this->category->filterable_attributes : [];

        foreach ($originalKeys as $index => $oldKey) {
            $newKey = trim($this->form->filterable_attributes[$index] ?? '');

            if ($newKey !== '' && $newKey !== $oldKey) {
                $renames[$oldKey] = $newKey;
            }
        }

        /** @var User $admin */
        $admin = Auth::user();

        $this->category = $action->handle(
            request(),
            $admin,
            $this->form->toCategoryAttributes(),
            $this->category,
            $renames,
        );

        $this->redirect(route('categories.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.category-form');
    }
}
