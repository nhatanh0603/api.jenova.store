<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductSimpleCollection;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response(gatherChilds(Category::all()));
    }

    public function show($id, $record = 15)
    {
        return new ProductSimpleCollection(Category::findOrFail($id)->products()->cursorPaginate($record));
    }
}
