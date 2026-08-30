<?php

use App\Models\Category;
use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\UpdateCategory;
use \Livewire\WithPagination;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Flux\Flux;
use App\Enums\UserRole;

new class extends Component
{
    use WithPagination;

    public $sortBy = 'name';
    public $sortDirection = 'desc';
    public ?int $editingCategoryId = null;
    public $categoryData = [
        'name' => null,
        'slug' => null,
    ];

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function createCategory(): void {
        $this->resetCategoryForm();
        Flux::modal('category-modal')->show();
    }

    public function editCategory(int $id): void {
        $category = Category::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryData = ['name' => $category->name, 'slug' => $category->slug];
        $this->resetValidation();
        Flux::modal('category-modal')->show();
    }

    public function saveCategory(CreateCategory $createCategory, UpdateCategory $updateCategory): void {
        $validated = $this->validate(['categoryData.name' => ['required', 'string', 'max:255']])['categoryData'];

        if ($this->editingCategoryId) {
            $updateCategory->handle(Category::findOrFail($this->editingCategoryId), $validated);
            $message = 'Categoría actualizada correctamente.';
        } else {
            $createCategory->handle($validated);
            $message = 'Categoría creada correctamente.';
        }
        Flux::modal('category-modal')->close();
        Flux::toast(variant: 'success', text: $message);
        $this->resetCategoryForm();
    }

    public function deleteCategory(int $id): void {
        abort_unless(auth()->user()->role === UserRole::ADMIN, 403);

        $category = Category::withCount('articles')->findOrFail($id);
        if ($category->articles_count > 0) {
            Flux::toast(variant: 'danger', text: 'No puedes eliminar una categoría que contiene artículos.');
            return;
        }
        $category->delete();
        $this->resetPage();
        Flux::toast(variant: 'success', text: 'Categoría eliminada correctamente.');
    }

    private function resetCategoryForm(): void {
        $this->editingCategoryId = null;
        $this->categoryData = ['name' => '', 'slug' => ''];
        $this->resetValidation();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()->withCount('articles')
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(5);
    }
};
?>

 <div>
    <flux:heading size="xl" level="1">Administración de usuarios</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Crea y edita usuarios</flux:text>

    <div class="flex justify-end gap-4">
        <flux:button wire:click="createCategory">Nueva categoría</flux:button>
    </div>
    <flux:table :paginate="$this->categories">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Categoria</flux:table.column>
        <flux:table.column >Articulos relacionados</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Fecha de creación</flux:table.column>
        <flux:table.column>Acciones</flux:table.column>

    </flux:table.columns>

    <flux:table.rows>
        @foreach ($this->categories as $category)
            <flux:table.row :key="$category->id">
                <flux:table.cell class="flex items-center gap-3">
                    {{ $category->name }}
                </flux:table.cell>
                 <flux:table.cell class="py-0">
                    <flux:badge size="sm">{{ $category->articles_count }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">{{ $category->created_at }}</flux:table.cell>
                <flux:table.cell class="py-0">
                    <div class="flex gap-2">
                        <flux:button wire:click="editCategory({{ $category->id }})" variant="ghost" size="sm" icon="pencil-square">Editar</flux:button>
                        @if (auth()->user()->role === UserRole::ADMIN)
                            <flux:button wire:click="deleteCategory({{ $category->id }})" wire:confirm="¿Eliminar esta categoría?" variant="danger" size="sm" icon="trash">Eliminar</flux:button>
                        @endif
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
<flux:modal name="category-modal" class="md:w-96">
    <form wire:submit="saveCategory">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingCategoryId ? 'Editar categoría' : 'Nueva categoría' }}</flux:heading>
            </div>
            <flux:input label="Nombre" wire:model="categoryData.name" />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
    </div>
    </form>
</flux:modal>
 </div>
