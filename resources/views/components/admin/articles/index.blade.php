<?php

use App\Models\Article;
use App\Models\Category;
use App\Actions\Articles\CreateArticle;
use App\Actions\Articles\UpdateArticle;
use \Livewire\WithPagination;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Enums\UserRole;

new class extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $sortBy = 'title';
    public $sortDirection = 'desc';
    public ?int $editingArticleId = null;
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
        if ($this->image) {
            $this->image->delete();
        }
        $this->image = null;
    }

    public function mount() {
        $this->categories = Category::all();
        $this->articleData['category_id'] = $this->categories->first()?->id;
    }

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function createArticle(): void {
        $this->resetForm();
        Flux::modal('article-modal')->show();
    }

    public function editArticle(int $id): void {
        $article = $this->manageableArticles()->findOrFail($id);
        $this->editingArticleId = $article->id;
        $this->articleData = $article->only(['user_id', 'category_id', 'title', 'slug', 'content', 'featured_image', 'published_at']);
        $this->isPublished = $article->published_at !== null;
        $this->image = null;
        $this->resetValidation();
        Flux::modal('article-modal')->show();
    }

    public function saveArticle(CreateArticle $createArticle, UpdateArticle $updateArticle): void {
        $validated = $this->validate([
            'articleData.title' => ['required', 'string', 'max:255'],
            'articleData.category_id' => ['required', 'integer', 'exists:categories,id'],
            'articleData.content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:1024'],
        ])['articleData'];

        $article = $this->editingArticleId
            ? $this->manageableArticles()->findOrFail($this->editingArticleId)
            : null;

        if ($article) {
            $updateArticle->handle($article, $validated, $this->image, $this->isPublished);
            $message = 'Artículo actualizado correctamente.';
        } else {
            $createArticle->handle(auth()->user(), $validated, $this->image, $this->isPublished);
            $message = 'Artículo creado correctamente.';
        }

        Flux::modal('article-modal')->close();
        Flux::toast(variant: 'success', text: $message);
        $this->resetForm();
    }

    public function deleteArticle(int $id): void {
        $article = $this->manageableArticles()->findOrFail($id);
        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        }
        $article->delete();
        $this->resetPage();
        Flux::toast(variant: 'success', text: 'Artículo eliminado correctamente.');
    }

    function resetForm() {
        $this->editingArticleId = null;
        $this->articleData = [
            'user_id' => null,
            'category_id' => $this->categories->first()?->id,
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
        return $this->manageableArticles()->with(['category', 'user'])
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(5);
    }

    private function manageableArticles()
    {
        return Article::query()->when(
            auth()->user()->role !== UserRole::ADMIN,
            fn ($query) => $query->whereBelongsTo(auth()->user()),
        );
    }
};
?>

 <div>
    <flux:heading size="xl" level="1">Administración de Articulos</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Crea y edita articulos</flux:text>

    <div class="flex justify-end gap-4">
        <flux:button wire:click="createArticle">Nuevo artículo</flux:button>
    </div>
    <flux:table :paginate="$this->articles">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'title'" :direction="$sortDirection" wire:click="sort('title')">Título</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'category_id'" :direction="$sortDirection" wire:click="sort('category_id')">Categoría</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'user_id'" :direction="$sortDirection" wire:click="sort('user_id')">Creado por</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'published_at'" :direction="$sortDirection" wire:click="sort('published_at')">Fecha de publicación</flux:table.column>
        <flux:table.column>Acciones</flux:table.column>
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
                    <div class="flex gap-2">
                        <flux:button wire:click="editArticle({{ $article->id }})" variant="ghost" size="sm" icon="pencil-square">Editar</flux:button>
                        <flux:button wire:click="deleteArticle({{ $article->id }})" wire:confirm="¿Eliminar este artículo?" variant="danger" size="sm" icon="trash">Eliminar</flux:button>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
<flux:modal name="article-modal"  flyout variant="floating" class="md:w-lg">
    <form wire:submit="saveArticle">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ $editingArticleId ? 'Editar artículo' : 'Nuevo artículo' }}</flux:heading>
                    </div>
                    <section class="relative border-0 p-0 bg-transparent">
                        <input
                        x-ref="imageInput"
                        type="file"
                        class="sr-only"
                        tabindex="-1"
                        wire:model="image"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        style="position: absolute; width: 1px; height: 1px; padding: 0px; margin: -1px; overflow: hidden; clip: rect(0px, 0px, 0px, 0px); white-space: nowrap; border: 0px;">

                        <div
                            wire:loading.flex
                            wire:target="image"
                            class="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-white/80 dark:bg-zinc-900/80"
                        >
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <flux:icon.loading class="size-5" />
                                Cargando imagen...
                            </div>
                        </div>
                        @if ($image)
                            <div class="max-w-full max-h-[200px] relative">
                                <div class="absolute right-0 top-0">
                                    <flux:button type="button" wire:click="removeImage" size="sm" icon="x-mark" variant="ghost" inset />
                                </div>

                                <img
                                    src="{{ $image->temporaryUrl() }}"
                                    alt="Vista previa"
                                    style="height: 200px;width: 100%;"
                                    class="object-cover rounded-lg"
                                >
                            </div>
                        @else
                        @if ($articleData['featured_image'])
                            <div class="max-w-full h-[200px] mb-3">
                                <img src="{{ Storage::url($articleData['featured_image']) }}" alt="Imagen actual" class="object-cover w-full h-[200px] rounded-lg">
                            </div>
                        @endif
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

                    @error('image')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div>
                    <flux:input label="Título" wire:model="articleData.title" />
                        @error('articleData.title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                    <flux:select placeholder="Selecciona la categoría" wire:model="articleData.category_id">
                        @forelse ($categories as $category)
                            <flux:select.option :label="$category->name" :value="$category->id"></flux:select.option>
                        @empty
                            <flux:select.option label="No hay categorías registradas" value="" />
                        @endforelse
                    </flux:select>
                        @error('articleData.category_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:textarea
                            wire:model="articleData.content"
                            label="Contenido"
                            placeholder="Lorem ipsum..."
                        />
                        @error('articleData.content')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <flux:switch wire:model.live="isPublished" label="Publicar al crear" />
                        <flux:spacer />
                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                            wire:target="image,saveArticle"
                        >
                            <span wire:loading.remove wire:target="saveArticle">Guardar</span>
                            <span wire:loading.flex wire:target="saveArticle" class="items-center gap-2">
                                <flux:icon.loading class="size-4" />
                                Guardando...
                            </span>
                        </flux:button>
                    </div>
            </div>
    </form>
</flux:modal>
 </div>
