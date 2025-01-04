<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;

class TagController
{
    use ApiResponse;

    public function index()
    {
        $cached = Redis::get('tags.index');

        if ($cached) {
            $tags = json_decode($cached);
            return $this->sendResponse($tags, 'Tag fetched successfully from cache');
        }

        $tags = Tag::select(['id', 'tag', 'slug'])->get();

        Redis::setex('tags.index', 3600, json_encode($tags));

        return $this->sendResponse($tags, 'Tag fetched successfully');
    }

    public function store(Request $request)
    {
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
    }

    public function show($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return $this->sendError('Tag not found', 404);
        }

        $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

        return $this->sendResponse($data, 'Tag fetched successfully');
    }

    public function update(Request $request, $id)
    {
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
    }

    public function destroy($id)
    {
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
    }

    public function trashed()
    {
        $cached = Redis::get('tags.trashed');

        if ($cached) {
            $tags = json_decode($cached);
            return $this->sendResponse($tags, 'Tag fetched successfully from cache');
        }

        $tags = Tag::onlyTrashed()->select(['id', 'tag', 'slug'])->get();

        Redis::setex('tags.trashed', 3600, json_encode($tags));

        return $this->sendResponse($tags, 'Tag fetched successfully');
    }

    public function restore($id)
    {
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
    }

    public function forceDelete($id)
    {
        $tag = Tag::onlyTrashed()->where('id', $id)->first();

        if (!$tag) {
            return $this->sendError('Tag not found', 404);
        }

        $tag->forceDelete();

        $data = array_merge(['id' => $tag->id], $tag->only($tag->getFillable()));

        Activity::all()->last();

        Redis::del('tags.trashed');

        return $this->sendResponse($data, 'Tag deleted permanently');
    }

    public function search(Request $request)
    {
        if (!$request->q) {
            $cached = Redis::get('tags.index');

            if ($cached) {
                $tags = json_decode($cached);
                return $this->sendResponse($tags, 'Tags fetched successfully from cache');
            }

            $tags = Tag::select(['id', 'tag', 'slug'])->get();

            Redis::setex('tags.index', 3600, json_encode($tags));
        }

        $tags = Tag::where('tag', 'like', '%' . $request->q . '%')->select(['id', 'tag', 'slug'])->get();

        if (!$tags) {
            return $this->sendError('Tags not found', 404);
        }

        return $this->sendResponse($tags, 'Tags fetched successfully');
    }
}
