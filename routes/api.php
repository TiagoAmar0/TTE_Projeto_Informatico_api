<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShiftController;
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
   })->middleware('auth.admin');

   Route::group(['prefix' => 'services'], function(){
       Route::get('', [ServiceController::class, 'index'])->middleware('auth.admin');
       Route::post('', [ServiceController::class, 'store'])->middleware('auth.admin');

       Route::group(['prefix' => '{service:id}'], function(){
           Route::get('', [ServiceController::class, 'show'])->middleware('can:is-service-lead,service');
           Route::put('', [ServiceController::class, 'update'])->middleware('auth.admin');
           Route::delete('', [ServiceController::class, 'destroy'])->middleware('auth.admin');
           Route::group(['prefix' => 'users'], function(){
               Route::put('{user}', [ServiceController::class, 'associateUserToService'])->middleware('can:is-service-lead,service');
               Route::delete('{user}', [ServiceController::class, 'disassociateUserToService'])->middleware('can:is-service-lead,service');
           });
       });
   });

   Route::group(['prefix' => 'shifts'], function(){
      Route::group(['prefix' => '{shift:id}'], function(){
         Route::group(['prefix' => 'users'], function(){
           Route::group(['prefix' => '{user:id}'], function(){
                Route::post('', [ShiftController::class, 'associateNurseToShift']);
                Route::delete('', [ShiftController::class, 'disassociateNurseToShift']);
           });
         });
      });
   });
});
