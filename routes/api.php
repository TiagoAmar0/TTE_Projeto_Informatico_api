<?php

use Illuminate\Support\Facades\Route;

/**
 * Controllers
 */
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

/**
 * Guest routes
 */
Route::post('login', [UserController::class, 'login']);

/**
 * Routes protected by authentication
 */
Route::middleware('auth:sanctum')->group(function(){
    Route::get('me', [UserController::class, 'me']);
    Route::delete('logout', [UserController::class, 'logout']);

    /**
     * Users
     */
   Route::group(['prefix' => 'users'], function(){
      Route::get('', [UserController::class, 'index']);
      Route::post('', [UserController::class, 'store']);
   });

   Route::group(['prefix' => 'roles'], function(){
      Route::get('', [RoleController::class, 'index']);
   });

});
