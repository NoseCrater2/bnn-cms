<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::view('/', 'admin.dashboard')
            ->name('dashboard');

        Route::resource(
            'users',
            UserController::class
        );

        Route::resource(
            'articles',
            ArticleController::class
        );

        Route::resource(
            'categories',
            CategoryController::class
        );
    });

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('articulos', [ArticleController::class, 'index'])->name('public.articles');
});

require __DIR__.'/settings.php';
