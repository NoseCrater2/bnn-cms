<?php

use App\Models\Article;
use App\Models\Category;
use \Livewire\WithPagination;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $sortBy = 'title';
    public $sortDirection = 'desc';
    public $showUserModal = true;
    public $isPublished = false;
    public $articleData = [
        'user_id' => null,
        'category_id' => null,
        'title' => null,
        'slug' => null,
        'content' => null,
        'featured_image' => null,
        'published_at' => null,
    ];
    public Collection $categories;

    #[Validate('image|max:1024')]
    public $image;
    public function removeImage()
    {
        $this->image->delete();
        $this->articleData['featured_image'] = null;
        $this->image = null;
    }

    public function saveImage()
    {
       $this->articleData['featured_image'] = $this->image->store('articles', 'public');
    }

    public function mount() {
        $this->categories = Category::all();
    }

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function saveArticle() {

        $this->saveImage();
        $this->articleData['slug'] = Str::slug($this->articleData['title']);
        $this->articleData['user_id'] = auth()->id();
        if($this->isPublished){
             $this->articleData['published_at'] = now();
        }
        Article::create($this->articleData);

        Flux::modal('article-modal')->close();
        $this->resetForm();
    }

    function resetForm() {
        $this->articleData = [
            'user_id' => null,
            'category_id' => null,
            'title' => null,
            'slug' => null,
            'content' => null,
            'featured_image' => null,
            'published_at' => null,
        ];
        $this->image = null;
        $this->isPublished = false;
    }

    #[Computed]
    public function articles()
    {
        return Article::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(5);
    }
};
?>

 <div>
    <flux:heading size="xl" level="1">Administración de Articulos</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Crea y edita articulos</flux:text>

    <div class="flex justify-end gap-4">
        <flux:modal.trigger name="article-modal">
            <flux:button>Nuevo artículo</flux:button>
        </flux:modal.trigger>
    </div>
    <flux:table :paginate="$this->articles">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'title'" :direction="$sortDirection" wire:click="sort('title')">Título</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'category_id'" :direction="$sortDirection" wire:click="sort('category_id')">Categoría</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'user_id'" :direction="$sortDirection" wire:click="sort('user_id')">Creado por</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'published_at'" :direction="$sortDirection" wire:click="sort('published_at')">Fecha de publicación</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($this->articles as $article)
            <flux:table.row :key="$article->id">
                <flux:table.cell class="flex items-center gap-3">
                    {{ $article->title }}
                </flux:table.cell>
                <flux:table.cell class="py-0">
                    <flux:badge size="sm">{{ $article->category->name }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">{{ $article->user->name }}</flux:table.cell>

                <flux:table.cell class="py-0">
                  {{ $article->published_at??'No publicada' }}
                </flux:table.cell>


                <flux:table.cell class="py-0">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"></flux:button>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
<flux:modal name="article-modal"  flyout variant="floating" class="md:w-lg">
    <form wire:submit="saveArticle">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Nuevo artículo</flux:heading>
                    </div>
                    <section class="relative border-0 p-0 bg-transparent"  >
                        <input
                        x-ref="imageInput"
                        type="file"
                        class="sr-only"
                        tabindex="-1"
                        wire:model="image"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        style="position: absolute; width: 1px; height: 1px; padding: 0px; margin: -1px; overflow: hidden; clip: rect(0px, 0px, 0px, 0px); white-space: nowrap; border: 0px;">
                        @if ($image)
                            <div class="max-w-full max-h-[200px] relative">
                                <div class="absolute right-0 top-0">
                                    <flux:button wire:click="removeImage" size="sm" icon="x-mark" variant="ghost" inset />
                                    @error('image')
                                        <p class="text-sm text-red-500">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <img
                                    src="{{ $image->temporaryUrl() }}"
                                    alt="Vista previa"
                                    style="height: 200px;width: 100%;"
                                    class="object-cover rounded-lg"
                                >
                            </div>
                        @else
                        <div
                        @click="$refs.imageInput.click()"
                        class="w-full py-5 p-6 sm:py-10 sm:px-16 flex flex-col items-center justify-center rounded-lg border-dashed border-zinc-200 dark:border-white/10 border-2 bg-zinc-50 dark:bg-white/10 transition-colors in-data-dragging:bg-zinc-100 in-data-dragging:border-zinc-300 dark:in-data-dragging:bg-white/15 dark:in-data-dragging:border-white/20 [[disabled]_&amp;]:opacity-75 [[disabled]_&amp;]:pointer-events-none">
                            <div class="relative mb-4"></div>
                            <div class="flex flex-col items-center gap-2">
                                <div class="text-sm font-medium text-zinc-800 dark:text-white cursor-default [[disabled]_&amp;]:opacity-75">
                                        Haz click aquí para seleccionar una imagen
                                </div>
                                <div class="relative text-zinc-500 dark:text-white/60 cursor-default text-sm">
                                    JPG, PNG max 1MB
                                </div>
                            </div>
                        </div>
                        @endif

                    </section>
                    <flux:input label="Título" wire:model="articleData.title" />
                    <flux:select placeholder="Categoría"  wire:model="articleData.category_id" placeholder="Selecciona la categoría">
                        @forelse ($categories as $category)
                            <flux:select.option :label="$category->name" :value="$category->id"></flux:select.option>
                        @empty
                            <flux:select.option label="No hay categorías registradas" value=""></flux:select.option>
                        @endforelse

                    </flux:select>
                    <flux:textarea
                        wire:model="articleData.content"
                        label="Contendio"
                        placeholder="Lorem ipsum..."
                    />
                    <div class="flex items-center">
                        <flux:switch wire:model.live="isPublished" label="Publicar al crear" />
                        <flux:spacer />
                        <flux:button type="submit" variant="primary">Guardar</flux:button>
                    </div>
            </div>
    </form>
</flux:modal>
 </div>
