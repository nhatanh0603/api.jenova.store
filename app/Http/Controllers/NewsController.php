<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsDetailResource;
use App\Http\Resources\NewsResource;
use App\Models\News;

class NewsController extends Controller
{
    public function index($record = 5)
    {
        return NewsResource::collection(News::cursorPaginate($record));
    }

    public function show($slug)
    {
        return new NewsDetailResource(News::where('slug', $slug)->firstOrFail());
    }
}
