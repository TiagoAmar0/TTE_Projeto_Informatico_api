<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;


/**
 * Controllers
 */
use App\Http\Controllers\UserController;

/**
 * Guest routes
 */
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::put('reset-password', [AuthController::class, 'resetPassword']);

/**
 * Routes protected by authentication
 */
Route::middleware('auth:api')->group(function(){
    Route::get('me', [AuthController::class, 'me']);
    Route::delete('logout', [AuthController::class, 'logout']);
    Route::put('password', [AuthController::class, 'changePassword']);

    /**
     * Users
     */
   Route::group(['prefix' => 'users'], function(){
      Route::get('', [UserController::class, 'index']);
      Route::post('', [UserController::class, 'store']);

      Route::group(['prefix' => '{user:id}'], function(){
         Route::get('', [UserController::class, 'show']);
         Route::put('', [UserController::class, 'update']);
         Route::delete('', [UserController::class, 'destroy']);
      });
   });

   Route::group(['prefix' => 'services'], function(){
       Route::get('', [ServiceController::class, 'index']);
       Route::post('', [ServiceController::class, 'store']);

       Route::group(['prefix' => '{service:id}'], function(){
           Route::get('', [ServiceController::class, 'show']);
           Route::put('', [ServiceController::class, 'update']);
           Route::delete('', [ServiceController::class, 'destroy']);
           Route::group(['prefix' => 'users'], function(){
               Route::put('{user}', [ServiceController::class, 'associateUser']);
               Route::delete('{user}', [ServiceController::class, 'disassociateUser']);
           });
       });
   });
});
