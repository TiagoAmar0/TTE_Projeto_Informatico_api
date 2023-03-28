<?php

use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;


header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

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

   Route::group(['prefix' => 'services'], function(){
       Route::get('', [ServiceController::class, 'index']);

       Route::group(['prefix' => '{service:id}'], function(){
           Route::get('', [ServiceController::class, 'show']);
           Route::delete('', [ServiceController::class, 'destroy']);
       });
   });

   Route::group(['prefix' => 'roles'], function(){
      Route::get('', [RoleController::class, 'index']);
   });

});
