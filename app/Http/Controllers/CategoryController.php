<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSimpleCollection;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Mockery\Generator\StringManipulation\Pass\CallTypeHintPass;

class CategoryController extends Controller
{
    public function index()
    {
        return response(gatherChilds(Category::all()));
    }


    /**
     * show products of a category
     *
     * @param  integer $id category_id
     * @param  integer $record
     * @param  string $column desc asc
     * @param  string $direction price complexity attack_type
     * @return array
     */
    public function show($id, $record = 15, $column = 'price', $direction = 'desc')
    {
        return ProductResource::collection(Category::findOrFail($id)
                                                    ->products_with_bonus($column, $direction)
                                                    ->cursorPaginate($record));
    }
}
