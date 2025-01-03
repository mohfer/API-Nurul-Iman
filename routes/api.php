<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\TagController;

// Login Endpoint
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Category Endpoint
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::put('categories/{slug}', [CategoryController::class, 'update']);
    Route::delete('categories/{slug}', [CategoryController::class, 'destroy']);
    Route::get('categories/trashed', [CategoryController::class, 'trashed']);
    Route::put('categories/trashed/{slug}', [CategoryController::class, 'restore']);
    Route::delete('categories/trashed/{slug}', [CategoryController::class, 'forceDelete']);

    // Tag Endpoint
    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);
    Route::get('tags/{slug}', [TagController::class, 'show']);
    Route::put('tags/{slug}', [TagController::class, 'update']);
    Route::delete('tags/{slug}', [TagController::class, 'destroy']);
    Route::get('tags/trashed', [TagController::class, 'trashed']);
    Route::put('tags/trashed/{slug}', [TagController::class, 'restore']);
    Route::delete('tags/trashed/{slug}', [TagController::class, 'forceDelete']);

    // News Endpoint
    Route::get('news', [NewsController::class, 'index']);
    Route::post('news', [NewsController::class, 'store']);
    Route::get('news/{slug}', [NewsController::class, 'show']);
    Route::put('news/{slug}', [NewsController::class, 'update']);
    Route::delete('news/{slug}', [NewsController::class, 'destroy']);
    Route::get('news/trashed', [NewsController::class, 'trashed']);
    Route::put('news/trashed/{slug}', [NewsController::class, 'restore']);
    Route::delete('news/trashed/{slug}', [NewsController::class, 'forceDelete']);
    Route::post('news/draft', [NewsController::class, 'draftNews']);
    Route::get('news/draft', [NewsController::class, 'showDraftNews']);
    Route::patch('news/{slug}', [NewsController::class, 'published']);
    Route::get('news/author/{slug}', [NewsController::class, 'showNewsByAuthor']);
    Route::get('news/category/{slug}', [NewsController::class, 'showNewsByCategory']);
    Route::get('news/tag/{slug}', [NewsController::class, 'showNewsByTag']);

    // Logout Endpoint
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
