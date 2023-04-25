<?php

use App\Http\Controllers\RolesController;
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
            Route::post('/logout', 'logout');

        });
        //Roles
        Route::controller(RolesController::class)->group(function () {
            Route::get('/roles', 'all');
        });
        //Business CRUD
        Route::controller(BusinessController::class)->group(function () {
            Route::get('/business/{business}', 'show');
            Route::get('/businesses', 'all');
            Route::post('/business', 'store');
            Route::put('/business/update/{business}', 'update');
            Route::delete('/businesses', 'delete');
        });
    });
});
