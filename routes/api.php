<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftUserController;
use App\Http\Controllers\SwapController;
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

   /** Services */
   Route::group(['prefix' => 'services'], function(){
       Route::get('', [ServiceController::class, 'index'])->middleware('auth.admin');
       Route::post('', [ServiceController::class, 'store'])->middleware('auth.admin');

       Route::group(['prefix' => '{service:id}'], function(){
           Route::get('', [ServiceController::class, 'show'])->middleware('can:is-service-lead,service');
           Route::put('', [ServiceController::class, 'update'])->middleware('auth.admin');
           Route::delete('', [ServiceController::class, 'destroy'])->middleware('auth.admin');

           Route::group(['prefix' => 'shifts'], function(){
              Route::get('', [ShiftController::class, 'index'])->middleware('can:is-service-lead,service');
              Route::post('', [ShiftController::class, 'store'])->middleware('can:is-service-lead,service');

              Route::group(['prefix' => '{shift}'], function(){
                  Route::get('', [ShiftController::class, 'show'])->middleware('can:is-service-lead,service');
                  Route::put('', [ShiftController::class, 'update'])->middleware('can:is-service-lead,service');
                  Route::delete('', [ShiftController::class, 'destroy'])->middleware('can:is-service-lead,service');
              });
           });

           Route::group(['prefix' => 'users'], function(){
               Route::put('{user}', [ServiceController::class, 'associateUserToService'])->middleware('can:is-service-lead,service');
               Route::delete('{user}', [ServiceController::class, 'disassociateUserToService'])->middleware('can:is-service-lead,service');
           });

           /**
            * Schedules
            */
           Route::group(['prefix' => 'schedules'], function(){
              Route::get('', [ScheduleController::class, 'index'])->middleware('can:is-service-lead,service');
              Route::post('', [ScheduleController::class, 'store'])->middleware('can:is-service-lead,service');

               Route::group(['prefix' => '{schedule:id}'], function(){
                   Route::get('', [ScheduleController::class, 'show'])->middleware('can:is-service-lead,service');
                   Route::get('export', [ScheduleController::class, 'export'])->middleware('can:is-service-lead,service');
                   Route::delete('', [ScheduleController::class, 'destroy'])->middleware('can:is-service-lead,service');
                   Route::put('', [ScheduleController::class, 'update'])->middleware('can:is-service-lead,service');
               });
           });
       });
   });

    /**
     * Shifts
     */
   Route::group(['prefix' => 'shifts'], function(){
      Route::group(['prefix' => '{shift:id}'], function(){
         Route::group(['prefix' => 'users'], function(){
           Route::group(['prefix' => '{user:id}'], function(){
                Route::post('', [ShiftController::class, 'associateNurseToShift'])->middleware('can:is-service-lead,service');
                Route::delete('', [ShiftController::class, 'disassociateNurseToShift'])->middleware('can:is-service-lead,service');
           });
         });
      });
   });

   /**
    * Swaps
    */
    Route::get('user-shifts', [ShiftUserController::class, 'index']);
    Route::group(['prefix' => 'swaps'], function(){
        Route::post('', [SwapController::class, 'store'])->middleware('can:is-not-admin');
        Route::get('proposed-to-user', [SwapController::class, 'swapsProposedToUser'])->middleware('can:is-not-admin');
        Route::get('user-proposed', [SwapController::class, 'swapsUserIsProposing'])->middleware('can:is-not-admin');
        Route::get('history', [SwapController::class, 'swapsHistory'])->middleware('can:is-not-admin');
        Route::group(['prefix' => '{swap}'], function (){
            Route::patch('approve', [SwapController::class, 'approveSwap'])->middleware('can:is-not-admin');
            Route::patch('reject', [SwapController::class, 'rejectSwap'])->middleware('can:is-not-admin');
        });
    });
});
