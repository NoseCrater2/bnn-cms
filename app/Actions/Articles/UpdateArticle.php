<?php

namespace App\Actions\Articles;

use App\Models\Article;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateArticle
{
    public function handle(Article $article, array $data, ?UploadedFile $image, bool $isPublished): Article
    {
        $oldImage = $article->featured_image;
        $data['user_id'] = $article->user_id;
        $data['slug'] = $this->uniqueSlug($data['title'], $article);
        $data['published_at'] = $isPublished
            ? ($article->published_at ?? now())
            : null;

        if ($image) {
            $data['featured_image'] = $image->store('articles', 'public');
        }

        $article->update($data);

        if ($image && $oldImage && $oldImage !== $article->featured_image) {
            Storage::disk('public')->delete($oldImage);
        }

        return $article->refresh();
    }

    private function uniqueSlug(string $title, Article $article): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (Article::where('slug', $slug)->whereKeyNot($article->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
