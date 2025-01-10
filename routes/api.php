<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\CategoryController;

// Public Endpoint
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verificationEmail'])->middleware(['signed'])->name('verification.verify');
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->middleware(['throttle:6,1'])->name('verification.send');
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('guest')->name('password.email');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');
});

Route::prefix('news')->group(function () {
    Route::get('/author/{slug}', [NewsController::class, 'showByAuthor']);
    Route::get('/category/{slug}', [NewsController::class, 'showByCategory']);
    Route::get('/tag/{slug}', [NewsController::class, 'showByTag']);
});

Route::middleware('auth:sanctum', 'verified')->group(function () {

    // User Endpoint
    Route::prefix('users')->group(function () {
        Route::prefix('trashed')->group(function () {
            Route::get('/', [UserController::class, 'trashed'])->middleware('permission:user.trashed');
            Route::put('/{id}', [UserController::class, 'restore'])->middleware('permission:user.restore');
            Route::delete('/{id}', [UserController::class, 'forceDelete'])->middleware('permission:user.forceDelete');
        });
        Route::get('/search', [UserController::class, 'search'])->middleware('permission:user.read');
        Route::get('/', [UserController::class, 'index'])->middleware('permission:user.read');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:user.create');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:user.read');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:user.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:user.delete');
    });

    // Category Endpoint
    Route::prefix('categories')->group(function () {
        Route::prefix('trashed')->group(function () {
            Route::get('/', [CategoryController::class, 'trashed'])->middleware('permission:category.trashed');
            Route::put('/{id}', [CategoryController::class, 'restore'])->middleware('permission:category.restore');
            Route::delete('/{id}', [CategoryController::class, 'forceDelete'])->middleware('permission:category.forceDelete');
        });
        Route::get('/search', [CategoryController::class, 'search'])->middleware('permission:category.read');
        Route::get('/', [CategoryController::class, 'index'])->middleware('permission:category.read');
        Route::post('/', [CategoryController::class, 'store'])->middleware('permission:category.create');
        Route::get('/{id}', [CategoryController::class, 'show'])->middleware('permission:category.read');
        Route::put('/{id}', [CategoryController::class, 'update'])->middleware('permission:category.update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('permission:category.delete');
    });

    // Tag Endpoint
    Route::prefix('tags')->group(function () {
        Route::prefix('trashed')->group(function () {
            Route::get('/', [TagController::class, 'trashed'])->middleware('permission:tag.trashed');
            Route::put('/{id}', [TagController::class, 'restore'])->middleware('permission:tag.restore');
            Route::delete('/{id}', [TagController::class, 'forceDelete'])->middleware('permission:tag.forceDelete');
        });
        Route::get('/search', [TagController::class, 'search'])->middleware('permission:tag.read');
        Route::get('/', [TagController::class, 'index'])->middleware('permission:tag.read');
        Route::post('/', [TagController::class, 'store'])->middleware('permission:tag.create');
        Route::get('/{id}', [TagController::class, 'show'])->middleware('permission:tag.read');
        Route::put('/{id}', [TagController::class, 'update'])->middleware('permission:tag.update');
        Route::delete('/{id}', [TagController::class, 'destroy'])->middleware('permission:tag.delete');
    });

    // Gallery Endpoint
    Route::prefix('galleries')->group(function () {
        Route::prefix('trashed')->group(function () {
            Route::get('/', [GalleryController::class, 'trashed'])->middleware('permission:gallery.trashed');
            Route::put('/{id}', [GalleryController::class, 'restore'])->middleware('permission:gallery.restore');
            Route::delete('/{id}', [GalleryController::class, 'forceDelete'])->middleware('permission:gallery.forceDelete');
        });
        Route::get('/search', [GalleryController::class, 'search'])->middleware('permission:gallery.read');
        Route::get('/', [GalleryController::class, 'index'])->middleware('permission:gallery.read');
        Route::post('/', [GalleryController::class, 'store'])->middleware('permission:gallery.create');
        Route::get('/{id}', [GalleryController::class, 'show'])->middleware('permission:gallery.read');
        Route::put('/{id}', [GalleryController::class, 'update'])->middleware('permission:gallery.update');
        Route::delete('/{id}', [GalleryController::class, 'destroy'])->middleware('permission:gallery.delete');
    });

    // Role Endpoint
    Route::prefix('roles')->group(function () {
        Route::get('/search', [RoleController::class, 'search'])->middleware('permission:role.read');
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:role.read');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:role.create');
        Route::get('/{id}', [RoleController::class, 'show'])->middleware('permission:role.read');
        Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:role.update');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:role.delete');
    });

    // News Endpoint
    Route::prefix('news')->group(function () {
        Route::prefix('trashed')->group(function () {
            Route::get('/', [NewsController::class, 'trashed'])->middleware('permission:news.trashed');
            Route::put('/{id}', [NewsController::class, 'restore'])->middleware('permission:news.restore');
            Route::delete('/{id}', [NewsController::class, 'forceDelete'])->middleware('permission:news.forceDelete');
        });
        Route::prefix('draft')->group(function () {
            Route::post('/', [NewsController::class, 'draftNews'])->middleware('permission:news.create');
            Route::get('/', [NewsController::class, 'showDraftNews'])->middleware('permission:news.read');
        });
        Route::patch('/{id}', [NewsController::class, 'published'])->middleware('permission:news.update');
        Route::get('/', [NewsController::class, 'index'])->middleware('permission:news.read');
        Route::post('/', [NewsController::class, 'store'])->middleware('permission:news.create');
        Route::get('/{id}', [NewsController::class, 'show'])->middleware('permission:news.read');
        Route::put('/{id}', [NewsController::class, 'update'])->middleware('permission:news.update');
        Route::delete('/{id}', [NewsController::class, 'destroy'])->middleware('permission:news.delete');
    });

    // Logout Endpoint
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
