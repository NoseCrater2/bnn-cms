<?php

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Support\Str;

class UpdateCategory
{
    public function handle(Category $category, array $data): Category
    {
        $data['slug'] = $this->uniqueSlug($data['name'], $category);
        $category->update($data);

        return $category->refresh();
    }

    private function uniqueSlug(string $name, Category $category): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->whereKeyNot($category->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
