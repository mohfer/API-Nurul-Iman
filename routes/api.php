<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\TagController;

// Public Endpoint
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('news/author/{slug}', [NewsController::class, 'showByAuthor']);
Route::get('news/category/{slug}', [NewsController::class, 'showByCategory']);
Route::get('news/tag/{slug}', [NewsController::class, 'showByTag']);

Route::middleware('auth:sanctum')->group(function () {

    // Category Endpoint
    Route::get('categories/trashed', [CategoryController::class, 'trashed']);
    Route::put('categories/trashed/{id}', [CategoryController::class, 'restore']);
    Route::delete('categories/trashed/{id}', [CategoryController::class, 'forceDelete']);
    Route::resource('categories', CategoryController::class);

    // Tag Endpoint
    Route::get('tags/trashed', [TagController::class, 'trashed']);
    Route::put('tags/trashed/{id}', [TagController::class, 'restore']);
    Route::delete('tags/trashed/{id}', [TagController::class, 'forceDelete']);
    Route::resource('tags', TagController::class);

    // News Endpoint
    Route::get('news/trashed', [NewsController::class, 'trashed']);
    Route::put('news/trashed/{id}', [NewsController::class, 'restore']);
    Route::delete('news/trashed/{id}', [NewsController::class, 'forceDelete']);
    Route::resource('news', NewsController::class);
    Route::post('news/draft', [NewsController::class, 'draftNews']);
    Route::get('news/draft', [NewsController::class, 'showDraftNews']);
    Route::patch('news/{id}', [NewsController::class, 'published']);

    // Logout Endpoint
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
