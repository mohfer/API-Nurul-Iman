<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;

class CategoryController
{
    use ApiResponse;

    public function index()
    {
        $cached = Redis::get('categories.index');

        if ($cached) {
            $categories = json_decode($cached);
            return $this->sendResponse($categories, 'Category fetched successfully from cache');
        }

        $categories = Category::select(['id', 'category', 'slug'])->get();

        Redis::setex('categories.index', 3600, json_encode($categories));

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

        $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

        Activity::all()->last();

        Redis::del('categories.index');

        return $this->sendResponse($data, 'Category created successfully', 201);
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

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

        $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

        Activity::all()->last();

        Redis::del('categories.index');

        return $this->sendResponse($data, 'Category updated successfully');
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $category->delete();

        $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

        Activity::all()->last();

        Redis::pipeline(function ($pipe) {
            $pipe->del('categories.index');
            $pipe->del('categories.trashed');
        });

        return $this->sendResponse($data, 'Category deleted successfully');
    }

    public function trashed()
    {
        $cached = Redis::get('categories.trashed');

        if ($cached) {
            $categories = json_decode($cached);
            return $this->sendResponse($categories, 'Category fetched successfully from cache');
        }

        $categories = Category::onlyTrashed()->select(['id', 'category', 'slug'])->get();

        Redis::setex('categories.trashed', 3600, json_encode($categories));

        return $this->sendResponse($categories, 'Category fetched successfully');
    }

    public function restore($id)
    {
        $category = Category::onlyTrashed()->where('id', $id)->first();

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $category->restore();

        $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

        Activity::all()->last();

        Redis::pipeline(function ($pipe) {
            $pipe->del('categories.index');
            $pipe->del('categories.trashed');
        });

        return $this->sendResponse($data, 'Category restored successfully');
    }

    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->where('id', $id)->first();

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        $category->forceDelete();

        $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

        Activity::all()->last();

        Redis::del('categories.trashed');

        return $this->sendResponse($data, 'Category deleted permanently');
    }

    public function search(Request $request)
    {
        if (!$request->has('q') || empty($request->q)) {
            $cached = Redis::get('categories.index');

            if ($cached) {
                $categories = json_decode($cached);
                return $this->sendResponse($categories, 'Categories fetched successfully from cache');
            }

            $categories = Category::select(['id', 'category', 'slug'])->get();

            Redis::setex('categories.index', 3600, json_encode($categories));

            return $this->sendResponse($categories, 'Categories fetched successfully');
        }

        $categories = Category::where('category', 'like', '%' . $request->q . '%')
            ->select(['id', 'category', 'slug'])
            ->get();

        if ($categories->isEmpty()) {
            return $this->sendError('No categories found matching your query', 404);
        }

        return $this->sendResponse($categories, 'Categories fetched successfully');
    }
}
