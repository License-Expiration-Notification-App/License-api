<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\SubsidiariesController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::get('confirm-registration', [AuthController::class, 'confirmRegistration']);
    Route::post('recover-password', [AuthController::class, 'recoverPassword']);
    Route::get('confirm-password-reset-token/{code}', [AuthController::class, 'confirmPasswordResetToken']);

    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    // Route::post('other-user-login', [AuthController::class, 'otherUserLogin']);
    // Route::put('sent-2fa-code/{user}', [AuthController::class, 'send2FACode']);
    // Route::put('confirm-2fa-code/{user}', [AuthController::class, 'confirm2FACode']);

    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        // Route::post('login-as', [AuthController::class, 'loginAs']);
        Route::get('user', [AuthController::class, 'fetchUser']); //->middleware('permission:read-users');
    });
});
Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('upload-user-photo', [UsersController::class, 'uploadPhoto']);
    /////////////////////CLIENTS/////////////////////////////
    Route::group(['prefix' => 'clients'], function () {
        Route::get('/', [ClientsController::class, 'index']);
        Route::get('show/{client}', [ClientsController::class, 'show']);
        
        Route::post('store', [ClientsController::class, 'store']);
        Route::put('update/{client}', [ClientsController::class, 'update']);
        Route::post('register-client-user', [ClientsController::class, 'registerClientUser']);
        Route::delete('delete-client-user/{user}', [ClientsController::class, 'deleteClientUser']);
        Route::put('change-client-status/{client}', [ClientsController::class, 'toggleClientStatus']);
        Route::post('upload-client-logo', [ClientsController::class, 'uploadClientLogo']);
    });
    Route::group(['prefix' => 'subsidiaries'], function () {
        Route::get('/', [SubsidiariesController::class, 'index']);
        Route::get('show/{subsidiary}', [SubsidiariesController::class, 'show']);
        
        Route::post('store', [SubsidiariesController::class, 'store']);
        Route::put('update/{subsidiary}', [SubsidiariesController::class, 'update']);
        Route::put('change-status/{subsidiary}', [SubsidiariesController::class, 'toggleSubsidiaryStatus']);
    });
});
