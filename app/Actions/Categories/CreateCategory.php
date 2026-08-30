<?php

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Support\Str;

class CreateCategory
{
    public function handle(array $data): Category
    {
        $data['slug'] = $this->uniqueSlug($data['name']);

        return Category::create($data);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
