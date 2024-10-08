<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('product/all', [App\Http\Controllers\ProductController::class, 'all']);
Route::resource('product', App\Http\Controllers\ProductController::class)->except('index', 'create', 'edit');


Route::get('devices/all', [App\Http\Controllers\DevicesController::class, 'all']);
Route::resource('devices', App\Http\Controllers\DevicesController::class)->except('index', 'create', 'edit');


Route::get('ad/all', [App\Http\Controllers\AdController::class, 'all']);
Route::resource('ad', App\Http\Controllers\AdController::class)->except('index', 'create', 'edit');


Route::get('marquee/all', [App\Http\Controllers\MarqueeController::class, 'all']);
Route::resource('marquee', App\Http\Controllers\MarqueeController::class)->except('index', 'create', 'edit');
