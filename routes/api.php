<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GenerateController;
use App\Http\Controllers\NewsController;
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

/* Route::middleware('auth:sanctum')->prefix('generate')->controller(GenerateController::class)->group(function() {
    Route::get('/media/{type}', 'generateMediaLinkForDownload');
    Route::get('/hero/{id}/{ability_only?}', 'generateHeroDetail');
    Route::get('/seeder/{type}', 'generateSeeder');
}); */


Route::prefix('auth')->controller(AuthController::class)->group(function() {
    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/user', 'show');
        Route::get('/cards', 'showCards');
        Route::post('/change-email/send-otp', 'sendOtpToCurrentEmail');
        Route::post('/change-email/verify-otp', 'verifyCurrentEmailCode');
        Route::post('/change-email/send-otp-new-email', 'sendOtpToNewEmail');
        Route::post('/change-email/update-email', 'updateNewEmail');
        Route::patch('/update', 'update');
        Route::patch('/update-password', 'updatePassword');
        Route::delete('/signout', 'signout');
    });

    Route::post('/signup', 'signup');
    Route::post('/signin', 'signin');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');
});

Route::prefix('product')->controller(ProductController::class)->group(function() {
    Route::get('/whole/{record?}', 'index');
    Route::get('/random/{quantum?}', 'random'); // chậm hơn so với dùng category
    Route::get('/{slug}', 'show');
});

Route::get('/search/{keyword}', [SearchController::class, 'search']);


Route::prefix('/news')->controller(NewsController::class)->group(function() {
    Route::get('/whole/{record?}', 'index');
    Route::get('/{slug}', 'show');

});

Route::prefix('category')->controller(CategoryController::class)->group(function() {
    Route::get('/whole', 'index'); //show all category (đã được nhóm)
    Route::get('/{id}/products', 'show'); //show all sản phẩm của category
    Route::get('/random/{quantum?}', 'random'); // faster
});

Route::middleware('auth:sanctum')->prefix('cart')->controller(CartController::class)->group(function() {
    Route::get('/whole', 'show');
    Route::post('/add', 'store');
    Route::post('/checkout', 'checkout');
    Route::patch('/quantity', 'edit');
    Route::delete('/delete', 'destroy');
});

Route::middleware('auth:sanctum')->prefix('order')->controller(OrderController::class)->group(function() {
    Route::get('/whole/{record?}', 'index');
    Route::get('/{id}', 'show');
    Route::post('/place', 'store');
});

/* Route::get('/mailable', function () {
    return new App\Mail\EmailChanged('E-mail address is successfully changed', App\Models\User::find(1));
}); */
