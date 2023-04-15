<?php

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BussineController;
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

        Route::controller(UserController::class)->group(function () {
            Route::get('/users', 'show');
            Route::get('/user/{user}', 'getUser');
            Route::post('/user', 'store');
            Route::put('/users/{user}', 'update');
            Route::delete('/users', 'deleteByIds');
            Route::post('/login', 'login')->withoutMiddleware('auth:sanctum');
            Route::post('/logout', 'logout');
        });

        Route::controller(BussineController::class)->group(function () {
            Route::get('/bussines', 'show');
            Route::post('/bussine', 'store');
        });
    });
});
