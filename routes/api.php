<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GenerateController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
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
    Route::get('/cards', [AuthController::class, 'showCards'])->middleware('auth:sanctum');
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'signin']);
    Route::patch('/update', [AuthController::class, 'update'])->middleware('auth:sanctum');
    Route::patch('/update-password', [AuthController::class, 'updatePassword'])->middleware('auth:sanctum');
    Route::delete('/signout', [AuthController::class, 'signout'])->middleware('auth:sanctum');
});

Route::prefix('product')->group(function() {
    Route::get('/whole/{record?}', [ProductController::class, 'index']);
    Route::get('/random/{quantum?}', [ProductController::class, 'random']); // chậm hơn so với dùng category
    Route::get('/{slug}', [ProductController::class, 'show']);
});

Route::get('/search/{keyword}', [SearchController::class, 'search']);

Route::prefix('category')->group(function(){
    Route::get('/whole', [CategoryController::class, 'index']); //show all category (đã được nhóm)
    Route::get('/{id}/products', [CategoryController::class, 'show']); //show all sản phẩm của category
    Route::get('/random/{quantum?}', [CategoryController::class, 'random']); // faster
});

Route::middleware('auth:sanctum')->prefix('cart')->group(function() {
    Route::get('/whole', [CartController::class, 'show']);
    Route::post('/add', [CartController::class, 'store']);
    Route::post('/checkout', [CartController::class, 'checkout']);
    Route::patch('/quantity', [CartController::class, 'edit']);
    Route::delete('/delete', [CartController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('order')->group(function() {
    Route::get('/whole/{record?}', [OrderController::class, 'index']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::post('/place', [OrderController::class, 'store']);
});
