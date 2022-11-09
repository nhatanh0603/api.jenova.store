<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GenerateController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->prefix('generate')->controller(GenerateController::class)->group(function() {
    Route::get('/media/{type}', 'generateMediaLinkForDownload');
    Route::get('/hero/{id}/{ability_only?}', 'generateHeroDetail');
    Route::get('/seeder/{type}', 'generateSeeder');
});


Route::prefix('auth')->group(function() {
    Route::get('/user', [AuthController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'signin']);
    Route::delete('/signout', [AuthController::class, 'signout'])->middleware('auth:sanctum');
});

Route::prefix('product')->group(function() {
    Route::get('/whole', [ProductController::class, 'index']);
    Route::get('/{slug}', [ProductController::class, 'show']);
});

Route::prefix('category')->group(function(){
    Route::get('/whole', [CategoryController::class, 'index']); //show all category (đã được nhóm)
    Route::get('/{id}/products', [CategoryController::class, 'show']); //show all sản phẩm của category
});

Route::middleware('auth:sanctum')->prefix('cart')->group(function() {
    Route::get('/whole', [CartController::class, 'show']);
    Route::post('/add', [CartController::class, 'store']);
    Route::delete('/delete', [CartController::class, 'destroy']);
});
