<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;

class TagController
{
    use ApiResponse;

    public function index()
    {
        try {
            $cached = Redis::get('tags.index');

            if ($cached) {
                $tags = json_decode($cached);
                return $this->sendResponse($tags, 'Tag fetched successfully from cache');
            }

            $tags = Tag::select(['id', 'tag', 'slug'])->get();

            if ($tags->isEmpty()) {
                return $this->sendResponse([], 'No tags found');
            }

            Redis::setex('tags.index', 3600, json_encode($tags));

            return $this->sendResponse($tags, 'Tag fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during fetching tags: ' . $e->getMessage());
            return $this->sendError('An error occurred while fetching tags');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'tag' => 'required|string|unique:tags'
            ]);

            $tag = Tag::create([
                'tag' => $request->tag
            ]);

            $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

            Activity::all()->last();

            Redis::del('tags.index');

            return $this->sendResponse($data, 'Tag created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error during creating tag: ' . $e->getMessage());
            return $this->sendError('An error occurred while creating tag');
        }
    }

    public function show($id)
    {
        try {
            $tag = Tag::find($id);

            if (!$tag) {
                return $this->sendError('Tag not found', 404);
            }

            $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

            return $this->sendResponse($data, 'Tag fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during showing tag: ' . $e->getMessage());
            return $this->sendError('An error occurred while showing tag');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tag = Tag::find($id);

            if (!$tag) {
                return $this->sendError('Tag not found', 404);
            }

            $request->validate([
                'tag' => 'required|string|' . ($tag->tag != $request->tag ? 'unique:tags' : '')
            ]);

            $tag->slug = null;
            $tag->tag = $request->tag;
            $tag->save();

            $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

            Activity::all()->last();

            Redis::del('tags.index');

            return $this->sendResponse($data, 'Tag updated successfully');
        } catch (\Exception $e) {
            Log::error('Error during updating tag: ' . $e->getMessage());
            return $this->sendError('An error occurred while updating tag');
        }
    }

    public function destroy($id)
    {
        try {
            $tag = Tag::find($id);

            if (!$tag) {
                return $this->sendError('Tag not found', 404);
            }

            $tag->delete();

            $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('tags.index');
                $pipe->del('tags.trashed');
            });

            return $this->sendResponse($data, 'Tag deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error during deleting tag: ' . $e->getMessage());
            return $this->sendError('An error occurred while deleting tag');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('tags.trashed');

            if ($cached) {
                $tags = json_decode($cached);
                return $this->sendResponse($tags, 'Tag fetched successfully from cache');
            }

            $tags = Tag::onlyTrashed()->select(['id', 'tag', 'slug'])->get();

            if ($tags->isEmpty()) {
                return $this->sendResponse([], 'No tags found');
            }

            Redis::setex('tags.trashed', 3600, json_encode($tags));

            return $this->sendResponse($tags, 'Tag fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during fetching trashed tags: ' . $e->getMessage());
            return $this->sendError('An error occurred while fetching trashed tags');
        }
    }

    public function restore($id)
    {
        try {
            $tag = Tag::onlyTrashed()->where('id', $id)->first();

            if (!$tag) {
                return $this->sendError('Tag not found', 404);
            }

            $tag->restore();

            $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('tags.index');
                $pipe->del('tags.trashed');
            });

            return $this->sendResponse($data, 'Tag restored successfully');
        } catch (\Exception $e) {
            Log::error('Error during restoring tag: ' . $e->getMessage());
            return $this->sendError('An error occurred while restoring tag');
        }
    }

    public function forceDelete($id)
    {
        try {
            $tag = Tag::onlyTrashed()->where('id', $id)->first();

            if (!$tag) {
                return $this->sendError('Tag not found', 404);
            }

            $tag->forceDelete();

            $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

            Activity::all()->last();

            Redis::del('tags.trashed');

            return $this->sendResponse($data, 'Tag deleted permanently');
        } catch (\Exception $e) {
            Log::error('Error during force deleting tag: ' . $e->getMessage());
            return $this->sendError('An error occurred while force deleting tag');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('tags.index');

                if ($cached) {
                    $tags = json_decode($cached);
                    return $this->sendResponse($tags, 'Tags fetched successfully from cache');
                }

                $tags = Tag::select(['id', 'tag', 'slug'])->get();

                if ($tags->isEmpty()) {
                    return $this->sendResponse([], 'No tags found');
                }

                Redis::setex('tags.index', 3600, json_encode($tags));

                return $this->sendResponse($tags, 'Tags fetched successfully');
            }

            $tags = Tag::where('tag', 'like', '%' . $request->q . '%')
                ->orWhere('slug', 'like', '%' . $request->q . '%')
                ->select(['id', 'tag', 'slug'])
                ->get();

            if ($tags->isEmpty()) {
                return $this->sendResponse([], 'No tags found matching your query');
            }

            return $this->sendResponse($tags, 'Tags fetched successfully');
        } catch (\Exception $e) {
            Log::error('Error during searching tags: ' . $e->getMessage());
            return $this->sendError('An error occurred while searching tags');
        }
    }
}
