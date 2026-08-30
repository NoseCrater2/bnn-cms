<?php

use App\Models\Article;
use App\Models\Category;
use Livewire\WithPagination;
use Livewire\Component;
use Livewire\Attributes\Url;

new class extends Component {
    use WithPagination;

    public string $search = '';

    #[Url(as: 'categoria')]
    public string $category = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->whereHas('articles', fn ($query) => $query->whereNotNull('published_at'))->orderBy('name')->get(),
            'articles' => Article::query()
                ->whereNotNull('published_at')
                ->with('category')
                ->when(
                    $this->search,
                    fn ($query) => $query->where(
                        'title',
                        'like',
                        "%{$this->search}%"
                    )
                )
                ->when($this->category, fn ($query) => $query->where('category_id', $this->category))
                ->latest()
                ->paginate(5),
        ];
    }

    public function goToDetails(string $slug){
        return $this->redirectRoute(
            'public.articles.show',
            parameters: ['slug' => $slug],
            navigate: true
        );
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Listado de artículos</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Se muestran los artículos publicados</flux:text>
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" label="Buscar" placeholder="Buscar por título..." icon="magnifying-glass" />
        <flux:select wire:model.live="category" label="Categoría">
            <flux:select.option value="">Todas las categorías</flux:select.option>
            @foreach ($categories as $categoryOption)
                <flux:select.option :value="(string) $categoryOption->id">{{ $categoryOption->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
     <div class="space-y-4 grid grid-cols-1 md:grid-cols-5 gap-4">
            @foreach ($articles as $article)
                <flux:card class="space-y-3 flex flex-col h-[400px]">
                     <div class="w-full h-[200px]">
                        @if ($article->featured_image)
                              <img  src="{{Storage::url($article->featured_image)}}" class="object-cover w-full h-[200px] rounded-xl">
                        @else
                            <div class="w-full h-[200px] flex justify-center items-center text-gray-300 bg-gray-400 rounded-xl">
                                Sin imagen
                            </div>
                        @endif

                    </div>
                    <div>
                        <flux:heading size="lg" class="line-clamp-2">{{$article->title}}</flux:heading>
                        <flux:text class="mt-2 inline">
                            <span>Categoría:</span> <flux:badge color="lime">{{ $article->category->name }}</flux:badge>
                        </flux:text>
                    </div>
                     <flux:spacer />
                    <div class="space-y-2 flex">

                        <flux:button  wire:click="goToDetails('{{$article->slug}}')" variant="primary" class="w-full">Leer</flux:button>
                    </div>
                </flux:card>
            @endforeach
    </div>

    @if ($articles->isEmpty())
        <flux:callout class="mt-4">No se encontraron artículos con los filtros seleccionados.</flux:callout>
    @endif

    <div class="px-2">
        {{ $articles->links() }}
    </div>
</div>
