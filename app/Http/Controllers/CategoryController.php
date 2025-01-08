<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;

class CategoryController
{
    use ApiResponse;

    public function index()
    {
        try {
            $cached = Redis::get('categories.index');

            if ($cached) {
                $categories = json_decode($cached);
                return $this->sendResponse($categories, 'Category fetched successfully from cache');
            }

            $categories = Category::select(['id', 'category', 'slug'])->get();

            if ($categories->isEmpty()) {
                return $this->sendResponse([], 'No categories found');
            }

            Redis::setex('categories.index', 3600, json_encode($categories));

            return $this->sendResponse($categories, 'Category fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during fetching categories: ' . $e->getMessage());
            return $this->sendError('An error occurred while fetching categories');
        }
    }

    public function store(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error during creating category: ' . $e->getMessage());
            return $this->sendError('An error occurred while creating category');
        }
    }

    public function show($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return $this->sendError('Category not found', 404);
            }

            $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

            return $this->sendResponse($data, 'Category fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during showing category: ' . $e->getMessage());
            return $this->sendError('An error occurred while showing category');
        }
    }

    public function update(Request $request, $id)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error during updating category: ' . $e->getMessage());
            return $this->sendError('An error occurred while updating category');
        }
    }

    public function destroy($id)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error during deleting category: ' . $e->getMessage());
            return $this->sendError('An error occurred while deleting category');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('categories.trashed');

            if ($cached) {
                $categories = json_decode($cached);
                return $this->sendResponse($categories, 'Category fetched successfully from cache');
            }

            $categories = Category::onlyTrashed()->select(['id', 'category', 'slug'])->get();

            if ($categories->isEmpty()) {
                return $this->sendResponse([], 'No categories found');
            }

            Redis::setex('categories.trashed', 3600, json_encode($categories));

            return $this->sendResponse($categories, 'Category fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during fething trashed categories: ' . $e->getMessage());
            return $this->sendError('An error occurred while fetching trashed categories');
        }
    }

    public function restore($id)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error during restoring category: ' . $e->getMessage());
            return $this->sendError('An error occurred while restoring category');
        }
    }

    public function forceDelete($id)
    {
        try {
            $category = Category::onlyTrashed()->where('id', $id)->first();

            if (!$category) {
                return $this->sendError('Category not found', 404);
            }

            $category->forceDelete();

            $data = array_merge(['id' => $category->id], $category->only($category->getFillable()));

            Activity::all()->last();

            Redis::del('categories.trashed');

            return $this->sendResponse($data, 'Category deleted permanently');
        } catch (\Exception $e) {
            Log::error('Error during force deleting category: ' . $e->getMessage());
            return $this->sendError('An error occurred while force deleting category');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('categories.index');

                if ($cached) {
                    $categories = json_decode($cached);
                    return $this->sendResponse($categories, 'Categories fetched successfully from cache');
                }

                $categories = Category::select(['id', 'category', 'slug'])->get();

                if ($categories->isEmpty()) {
                    return $this->sendResponse([], 'No categories found');
                }

                Redis::setex('categories.index', 3600, json_encode($categories));

                return $this->sendResponse($categories, 'Categories fetched successfully');
            }

            $categories = Category::where('category', 'like', '%' . $request->q . '%')
                ->orWhere('slug', 'like', '%' . $request->q . '%')
                ->select(['id', 'category', 'slug'])
                ->get();

            if ($categories->isEmpty()) {
                return $this->sendResponse([], 'No categories found matching your query');
            }

            return $this->sendResponse($categories, 'Categories fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during searching categories: ' . $e->getMessage());
            return $this->sendError('An error occurred while searching categories');
        }
    }
}
