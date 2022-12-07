<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Category;

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
    public function show($id)
    {
        $validated = request()->validate([
            'record' => 'required|string',
            'column' => 'required|string',
            'direction' => 'required|string',
            'primary_attr' => 'nullable|string',
            'attack_capability' => 'nullable|string',
            'complexity' => 'nullable|string',
        ]);

        return ProductResource::collection(Category::findOrFail($id)->products_with_bonus($validated)
                                                                    ->cursorPaginate($validated['record']));
    }

    /**
     * Get random list of products.
     *
     * @param  mixed $quantum
     * @return Response JsonResource
     */
    public function random($quantum = 9)
    {
        return response()->json([
            'data' => [
                'strength' => ProductSimpleResource::collection(
                    Category::find(2)->products()->inRandomOrder()->take($quantum)->get()
                ),
                'agility' => ProductSimpleResource::collection(
                    Category::find(3)->products()->inRandomOrder()->take($quantum)->get()
                ),
                'intelligence' => ProductSimpleResource::collection(
                    Category::find(4)->products()->inRandomOrder()->take($quantum)->get()
                )
            ]
        ]);
    }
}
