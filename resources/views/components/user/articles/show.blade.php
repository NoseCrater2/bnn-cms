<?php

use App\Models\Article;
use Livewire\Component;

new class extends Component
{
    public $slug;
    public Article $article;

    public function mount()  {
        $this->article = Article::whereSlug($this->slug)->with('user', 'category')->first();
    }


};
?>

<div>
    <flux:heading size="xl" level="1"> <flux:button href="{{ route('public.articles') }}" class="mr-2" size="sm" icon="arrow-left" variant="ghost" inset /> {{ $article->title }}</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Publicado por <strong> {{ $article->user->name }}</strong> {{  $article->published_at->diffForHumans()}}</flux:text>
    <div class="space-y-3 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="col-span-2">
            <flux:card class="space-y-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="w-full h-[200px] col-span-1">
                    @if ($article->featured_image)
                            <img  src="{{Storage::url($article->featured_image)}}" class="object-cover w-full h-[200px] rounded-xl">
                    @else
                        <div class="w-full h-[200px] flex justify-center items-center text-gray-300 bg-gray-400 rounded-xl">
                            Sin imagen
                        </div>
                    @endif

                </div>
                <div class="w-full col-span-2">
                    <flux:heading size="lg" class="line-clamp-2">{{$article->title}}</flux:heading>
                    <flux:text class="mt-2 inline">
                    {{$article->content }}
                    </flux:text>
                </div>
            </flux:card>
        </div>
         <div class="col-span-1">
            <flux:card>
                <flux:heading size="lg">Categoría <flux:badge color="lime">{{$article->category->name}}</flux:badge></flux:heading>
                <flux:text class="mt-2 mb-4">
                    <flux:button href="{{ route('public.articles') }}" variant="ghost">Ver más articulos de la misma categoría</flux:button>
                </flux:text>
            </flux:card>
        </div>
    </div>

</div>
