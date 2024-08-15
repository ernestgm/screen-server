<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\DevicesController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MarqueeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ScreenController;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'cors'])->group(function () {
    Route::prefix('v1')->group(function () {
        //Users
        Route::controller(UserController::class)->group(function () {
            Route::get('/users', 'all');
            Route::get('/user/{user}', 'show');
            Route::post('/user', 'store');
            Route::put('/user/update/{user}', 'update');
            Route::delete('/users', 'deleteByIds');
            Route::post('/login', 'login')->withoutMiddleware('auth:sanctum');
            Route::post('refresh-token', 'refreshToken')->withoutMiddleware('auth:sanctum');
            Route::post('/logout', 'logout');

        });
        //Roles
        Route::controller(RolesController::class)->group(function () {
            Route::get('/roles', 'all');
        });
        //Business CRUD
        Route::controller(BusinessController::class)->group(function () {
            Route::get('/business/jsonroute', 'findRoute');
            Route::get('/business/{business}', 'show');
            Route::get('/businesses', 'all');
            Route::post('/business', 'store');
            Route::put('/business/update/{business}', 'update');
            Route::delete('/businesses', 'delete');
            Route::get('/business/generate_json/{business}', 'generateJson');
            Route::get('/businesses/resume', 'getResumeByUserId');
        });
        // Area CRUD
        Route::controller(AreaController::class)->group(function () {
            Route::get('/area/{area}', 'show');
            Route::get('/areas', 'all');
            Route::post('/area', 'store');
            Route::put('/area/update/{area}', 'update');
            Route::delete('/areas', 'delete');
        });
        // Screen CRUD
        Route::controller(ScreenController::class)->group(function () {
            Route::get('/screen/{screen}', 'show');
            Route::get('/screens', 'all');
            Route::post('/screen', 'store');
            Route::put('/screen/update/{screen}', 'update');
            Route::delete('/screens', 'delete');
        });

        // Image CRUD
        Route::controller(ImageController::class)->group(function () {
            Route::get('/image/{image}', 'show');
            Route::get('/images', 'all');
            Route::get('/images/byScreen', 'allByDeviceCode');
            Route::post('/image', 'store');
            Route::post('/image/update/{image}', 'update');
            Route::delete('/images', 'delete');
        });
        // Product CRUD
        Route::controller(ProductController::class)->group(function () {
            Route::get('/product/{product}', 'show');
            Route::get('/products', 'all');
            Route::post('/product', 'store');
            Route::put('/product/update/{product}', 'update');
            Route::delete('/products', 'delete');
        });

        // Device CRUD
        Route::controller(DevicesController::class)->group(function () {
            Route::get('/device/{device}', 'show');
            Route::get('/devices', 'all');
            Route::get('/devices/byId', 'showByDeviceId');
            Route::post('/device', 'store');
            Route::put('/device/update/{device}', 'update');
            Route::delete('/devices', 'delete');
            Route::get('/devices/getScreen', 'screenByCode');
            Route::get('/devices/getMarquee', 'marqueeByCode');
        });
        // Marquee CRUD
        Route::controller(MarqueeController::class)->group(function () {
            Route::get('/marquee/{marquee}', 'show');
            Route::get('/marquees', 'all');
            Route::post('/marquee', 'store');
            Route::put('/marquee/update/{marquee}', 'update');
            Route::delete('/marquees', 'delete');
        });
        // Ad CRUD
        Route::controller(AdController::class)->group(function () {
            Route::get('/ad/{ad}', 'show');
            Route::get('/ads', 'all');
            Route::post('/ad', 'store');
            Route::put('/ad/update/{ad}', 'update');
            Route::delete('/ads', 'delete');
        });
    });
});
