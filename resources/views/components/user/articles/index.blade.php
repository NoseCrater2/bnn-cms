<?php

use App\Models\Article;
use Livewire\WithPagination;
use Livewire\Component;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
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
                ->latest()
                ->paginate(5),
        ];
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Listado de artículos</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Se muestran los artículos publicados</flux:text>
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

                        <flux:button variant="primary" class="w-full">Leer</flux:button>
                    </div>
                </flux:card>
            @endforeach
    </div>

    <div class="px-2">
        {{ $articles->links() }}
    </div>
</div>
