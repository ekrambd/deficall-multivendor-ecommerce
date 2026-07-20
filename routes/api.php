<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiController;

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

Route::middleware(['throttle:60,1'])->group(function () {

	//front part api's
	Route::get('/sliders', [ApiController::class, 'sliders']);
	Route::get('/home-categories', [ApiController::class, 'homeCategories']);
	Route::get('/latest-products', [ApiController::class, 'latestProducts']);
	Route::get('/best-seller', [ApiController::class, 'bestSeller']);
	Route::get('/admin-verify-products', [ApiController::class, 'adminVerifyProducts']);
	Route::get('/category-details/{id}', [ApiController::class, 'categoryDetails']);
	Route::get('/subcategory-details/{id}', [ApiController::class, 'subcategoryDetails']);
	Route::get('/product-details/{id}', [ApiController::class, 'productDetails']);
	Route::get('/shop', [ApiController::class, 'shop']);

	//Route::get('/subcategories', [ApiController::class, 'subcategories']);

	//user auth
	Route::post('user-signup', [ApiController::class, 'userSignup']);
    Route::post('user-signin', [ApiController::class, 'userSignin']);

    //vendor auth
    Route::post('vendor-signup', [ApiController::class, 'vendorSignup']);
    Route::post('vendor-signin', [ApiController::class, 'vendorSignin']);

    //global

    Route::get('/category-lists', [ApiController::class, 'categoryLists']);
    Route::get('/subcategory-lists', [ApiController::class, 'subcategoryLists']);
    Route::get('/variant-lists', [ApiController::class, 'variantLists']);

    Route::middleware(['auth:sanctum', 'role:2'])
    ->prefix('vendor')
    ->group(function () {
    	Route::post('signout', [ApiController::class, 'vendorSignout']);
        Route::post('save-product', [ApiController::class, 'saveProduct']);
        Route::get('/product-lists', [ApiController::class, 'productLists']);
        Route::get('/product/details/{id}', [ApiController::class, 'vendorproductDetails']);
        Route::patch('/product-update/{id}', [ApiController::class, 'productUpdate']);
        Route::delete('/product-delete/{id}', [ApiController::class, 'productDelete']);
        Route::post('/product-status-update', [ApiController::class, 'productStatusUpdate']);
        Route::get('/product-variant-lists/{id}', [ApiController::class, 'productVariantLists']);

    });
    
    Route::middleware(['auth:sanctum', 'role:3'])->group( function () {
        Route::post('user-signout', [ApiController::class, 'userSignOut']);
    });

    //order
    Route::post('save-order', [ApiController::class, 'saveOrder']);
    
});