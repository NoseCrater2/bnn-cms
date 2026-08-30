<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::view('dashboard', 'dashboard')
            ->name('dashboard');

        Route::resource(
            'users',
            UserController::class
        );

    });

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    });

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

});
Route::get('articulos', [ArticleController::class, 'index'])->name('public.articles');
Route::get('articulos/{slug}', [ArticleController::class, 'show'])->name('public.articles.show');
require __DIR__.'/settings.php';
