<?php

namespace App\Actions\Articles;

use App\Models\Article;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CreateArticle
{
    public function handle(User $author, array $data, ?UploadedFile $image, bool $isPublished): Article
    {
        $data['user_id'] = $author->getKey();
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['published_at'] = $isPublished ? now() : null;

        if ($image) {
            $data['featured_image'] = $image->store('articles', 'public');
        }

        return Article::create($data);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (Article::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
