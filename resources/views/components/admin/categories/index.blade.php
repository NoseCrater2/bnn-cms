<?php

use App\Models\Category;
use \Livewire\WithPagination;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public $sortBy = 'name';
    public $sortDirection = 'desc';
    public $showUserModal = true;
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

    public function saveCategory() {
        $this->categoryData['slug'] = Str::slug($this->categoryData['name']);
        Category::create($this->categoryData);
        Flux::modal('category-modal')->close();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(5);
    }
};
?>

 <div>
    <flux:heading size="xl" level="1">Administración de usuarios</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Crea y edita usuarios</flux:text>

    <div class="flex justify-end gap-4">
        <flux:modal.trigger name="category-modal">
            <flux:button>Nueva categoría</flux:button>
        </flux:modal.trigger>
    </div>
    <flux:table :paginate="$this->categories">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Categoria</flux:table.column>
        <flux:table.column >Articulos relacionados</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Fecha de creación</flux:table.column>

    </flux:table.columns>

    <flux:table.rows>
        @foreach ($this->categories as $category)
            <flux:table.row :key="$category->id">
                <flux:table.cell class="flex items-center gap-3">
                    {{ $category->name }}
                </flux:table.cell>
                 <flux:table.cell class="py-0">
                    <flux:badge size="sm">{{ $category->articles->count() }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">{{ $category->created_at }}</flux:table.cell>
                <flux:table.cell class="py-0">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"></flux:button>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
<flux:modal name="category-modal" class="md:w-96">
    <form wire:submit="saveCategory">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva categoría</flux:heading>
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
