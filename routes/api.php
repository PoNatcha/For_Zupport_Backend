<?php

use App\Http\Controllers\RestaurantsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//api for search restaurants
Route::get('/restaurants', [RestaurantsController::class, 'search']);
