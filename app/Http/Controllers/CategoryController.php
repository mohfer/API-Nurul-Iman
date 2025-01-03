<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class CategoryController
{
    use ApiResponse;

    public function index()
    {
        $categories = Category::all()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'category' => $category->category,
                    'slug' => $category->slug
                ];
            });

        return $this->sendResponse($categories, 'Category fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|unique:categories'
        ]);

        $category = Category::create([
            'category' => $request->category
        ]);

        $data = [
            'id' => $category->id,
            'category' => $category->category,
            'slug' => $category->slug
        ];

        Activity::all()->last();

        return $this->sendResponse($data, 'Category created successfully', 201);
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $data = [
            'id' => $category->id,
            'category' => $category->category,
            'slug' => $category->slug
        ];

        return $this->sendResponse($data, 'Category fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $request->validate([
            'category' => 'required|string|' . ($category->category != $request->category ? 'unique:categories' : '')
        ]);

        $category->slug = null;
        $category->category = $request->category;
        $category->save();

        $data = [
            'id' => $category->id,
            'category' => $category->category,
            'slug' => $category->slug
        ];

        Activity::all()->last();

        return $this->sendResponse($data, 'Category updated successfully');
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $category->delete();

        Activity::all()->last();

        return $this->sendResponse(null, 'Category deleted successfully');
    }

    public function trashed()
    {
        $categories = Category::onlyTrashed()->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'category' => $category->category,
                    'slug' => $category->slug
                ];
            });

        return $this->sendResponse($categories, 'Category fetched successfully');
    }

    public function restore($id)
    {
        $category = Category::onlyTrashed()->where('id', $id)->first();

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $category->restore();

        Activity::all()->last();

        return $this->sendResponse(null, 'Category restored successfully');
    }

    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->where('id', $id)->first();

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $category->forceDelete();

        Activity::all()->last();

        return $this->sendResponse(null, 'Category deleted permanently');
    }
}
