<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsTag;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class NewsController
{
    use ApiResponse;

    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Request $request)
    {
        //
    }

    public function update(Request $request)
    {
        //
    }

    public function destroy(Request $request)
    {
        //
    }

    public function trashed()
    {
        //
    }

    public function restore(Request $request)
    {
        //
    }

    public function forceDelete(Request $request)
    {
        //
    }

    public function draftNews(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'thumbnail' => 'required|image',
            'content' => 'required|string',
            'user_id' => 'required|integer',
            'category_id' => 'required|integer',
            'is_published' => 'required|boolean'
        ]);

        $news = News::create([
            'title' => $request->title,
            'thumbnail' => $request->thumbnail,
            'content' => $request->content,
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'is_published' => false
        ]);

        $data = [
            'id' => $news->id,
            'title' => $news->title,
            'slug' =>  $news->slug,
            'thumbnail' => $news->thumbnail,
            'content' => $news->content,
            'user_id' => $news->user()->id,
            'category_id' => $news->category_id,
            'is_published' => false
        ];

        Activity::all()->last();

        return $this->sendResponse($data, 'News drafted successfully', 201);
    }

    public function showDraftNews()
    {
        //
    }

    public function published(Request $request)
    {
        $news = News::where('slug', $request->slug)->first();

        if (!$news) {
            return $this->sendError('News not found', 404);
        }

        $request->validate([
            'title' => 'required|string',
            'thumbnail' => 'required|image',
            'content' => 'required|string',
            'category_id' => 'required|integer',
            'is_published' => 'required|boolean'
        ]);

        $news->slug = null;
        $news->title = $request->title;
        $news->thumbnail = $request->thumbnail;
        $news->content = $request->content;
        $news->category_id = $request->category_id;
        $news->is_published = true;
        $news->published_at = date(now());
        $news->save();

        $data = [
            'is_published' => $news->is_published
        ];

        Activity::all()->last();

        return $this->sendResponse($data, 'News successfully published');
    }

    public function showNewsByAuthor(Request $request)
    {
        //
    }

    public function showNewsByCategory(Request $request)
    {
        $category = Category::where('slug', $request->slug)->first();
        $news = News::where('category_id', $category->id)->get();
        $newsTags = NewsTag::with('tag')->where('news_id', $news->id)->get();
        $newsDetails = News::with('user', 'category')->where('category_id', $category->id)->where('is_published', true)->get();

        if (!$news) {
            return $this->sendError('News with category ' . $category->name . ' not found', 404);
        }

        foreach ($newsTags as $newsTag) {
            $tags = [
                'id' => $newsTag->tag->id,
                'tag' => $newsTag->tag->tag,
                'slug' => $newsTag->tag->slug
            ];
        }

        $data = [
            'id' => $newsDetails->id,
            'title' => $newsDetails->title,
            'thumbnail' => $newsDetails->thumbnail,
            'content' => $newsDetails->content,
            'author' => $newsDetails->user->name,
            'category' => $newsDetails->category->category,
            'tags' => $tags
        ];

        return $this->sendResponse($data, 'News fetched successfully');
    }

    public function ShowNewsByTag(Request $request)
    {
        //
    }
}
