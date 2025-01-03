<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class TagController
{
    use ApiResponse;

    public function index()
    {
        $tags = Tag::all()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'tag' => $tag->tag,
                    'slug' => $tag->slug
                ];
            });

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

        $data = [
            'id' => $tag->id,
            'tag' => $tag->tag,
            'slug' => $tag->slug
        ];

        Activity::all()->last();

        return $this->sendResponse($data, 'Tag created successfully', 201);
    }

    public function show($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return $this->sendError('Tag not found', 404);
        }

        $data = [
            'id' => $tag->id,
            'tag' => $tag->tag,
            'slug' => $tag->slug
        ];

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

        $data = [
            'id' => $tag->id,
            'tag' => $tag->tag,
            'slug' => $tag->slug
        ];

        Activity::all()->last();

        return $this->sendResponse($data, 'Tag updated successfully');
    }

    public function destroy($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return $this->sendError('Tag not found', 404);
        }

        $tag->delete();

        Activity::all()->last();

        return $this->sendResponse(null, 'Tag deleted successfully');
    }

    public function trashed()
    {
        $tags = Tag::onlyTrashed()->get()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'tag' => $tag->tag,
                    'slug' => $tag->slug
                ];
            });

        return $this->sendResponse($tags, 'Tag fetched successfully');
    }

    public function restore($id)
    {
        $tag = Tag::onlyTrashed()->where('id', $id)->first();

        if (!$tag) {
            return $this->sendError('Tag not found', 404);
        }

        $tag->restore();

        Activity::all()->last();

        return $this->sendResponse(null, 'Tag restored successfully');
    }

    public function forceDelete($id)
    {
        $tag = Tag::onlyTrashed()->where('id', $id)->first();

        if (!$tag) {
            return $this->sendError('Tag not found', 404);
        }

        $tag->forceDelete();

        Activity::all()->last();

        return $this->sendResponse(null, 'Tag deleted permanently');
    }
}
