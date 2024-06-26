<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LicensesController;
use App\Http\Controllers\ReportsController;
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
// Route::post('upload-bulk-licenses', [LicensesController::class, 'uploadBulkLicenses']);
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::get('confirm-registration', [AuthController::class, 'confirmRegistration']);
    Route::post('recover-password', [AuthController::class, 'recoverPassword']);
    Route::get('confirm-password-reset-token/{code}', [AuthController::class, 'confirmPasswordResetToken']);

    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    // Route::post('other-user-login', [AuthController::class, 'otherUserLogin']);
    // Route::put('sent-2fa-code/{user}', [AuthController::class, 'send2FACode']);
    // Route::put('confirm-2fa-code/{user}', [AuthController::class, 'confirm2FACode']);

    

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout']);
        // Route::post('login-as', [AuthController::class, 'loginAs']);
        Route::get('user', [AuthController::class, 'fetchUser']); //->middleware('permission:read-users');
    });
});
Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('fetch-states', [Controller::class, 'fetchStates']);
    Route::get('fetch-state-lgas', [Controller::class, 'stateLGAS']);


    Route::post('upload-user-photo', [UsersController::class, 'uploadPhoto']);
    /////////////////////CLIENTS/////////////////////////////
    Route::group(['prefix' => 'clients'], function () {
        Route::get('/', [ClientsController::class, 'index']);
        Route::get('fetch-all', [ClientsController::class, 'fetchAllClients']);
        
        Route::get('show/{client}', [ClientsController::class, 'show']);
        
        Route::post('store', [ClientsController::class, 'store']);
        Route::put('update/{client}', [ClientsController::class, 'update']);
        Route::post('register-client-user', [ClientsController::class, 'registerClientUser']);        
        Route::put('update-client-user/{user}', [ClientsController::class, 'updateClientUser']);
        Route::put('make-client-user-main-admin/{client}', [ClientsController::class, 'makeClientUserMainAdmin']);
        Route::delete('delete-client-user/{user}', [ClientsController::class, 'deleteClientUser']);
        Route::put('change-client-status/{client}', [ClientsController::class, 'toggleClientStatus']);
        Route::post('upload-client-logo', [ClientsController::class, 'uploadClientLogo']);
    });
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('client', [ReportsController::class, 'clientDataAnalysisDashbord']);
        Route::get('super-admin', [ReportsController::class, 'adminDataAnalysisDashbord']);
        
    });
    Route::group(['prefix' => 'licenses'], function () {
        Route::get('/', [LicensesController::class, 'index']);
        Route::get('renewal-period-count', [LicensesController::class, 'licenseRenewalPeriods']);
        
        Route::get('show/{license}', [LicensesController::class, 'show']);
        Route::get('fetch-license-activity-timeline/{license}', [LicensesController::class, 'licenseActivityTimeLine']);
        Route::get('fetch-license-upcoming-activites/{license}', [LicensesController::class, 'licenseUpcomingActivities']);
        
        Route::post('store', [LicensesController::class, 'store']);
        Route::put('update/{license}', [LicensesController::class, 'update']);
        Route::post('upload-certificate', [LicensesController::class, 'uploadCertificate']);
        Route::post('upload-report', [LicensesController::class, 'uploadReport']);
        Route::delete('destroy/{license}', [LicensesController::class, 'destroy']);

        Route::put('approve-report/{report}', [LicensesController::class, 'approveReport']);
        Route::put('reject-report/{report}', [LicensesController::class, 'rejectReport']);
        Route::put('approve-license-renewal/{license}', [LicensesController::class, 'approveLicenseRenewal']);
        Route::put('reject-license-renewal/{license}', [LicensesController::class, 'rejectLicenseRenewal']);

        Route::delete('delete-renewal-document/{renewal}', [LicensesController::class, 'deleteRenewalDocument']);
        Route::delete('delete-report-document/{upload}', [LicensesController::class, 'deleteReportDocument']);

        Route::get('fetch-license-types', [LicensesController::class, 'fetchLicenseTypes']);
        Route::get('fetch-minerals', [LicensesController::class, 'fetchMinerals']);
        Route::post('store-mineral', [LicensesController::class, 'storeMineral']);
        Route::put('update-mineral/{mineral}', [LicensesController::class, 'updateMineral']);
        Route::delete('delete-mineral/{mineral}', [LicensesController::class, 'deleteMineral']);
        Route::post('upload-bulk-licenses', [LicensesController::class, 'uploadBulkLicenses']);
    });
    Route::group(['prefix' => 'subsidiaries'], function () {
        Route::get('/', [SubsidiariesController::class, 'index']);
        Route::get('show/{subsidiary}', [SubsidiariesController::class, 'show']);
        Route::get('fetch-client-subsidiaries', [SubsidiariesController::class, 'fetchClientSubsidiaries']);
        
        Route::post('store', [SubsidiariesController::class, 'store']);
        Route::put('update/{subsidiary}', [SubsidiariesController::class, 'update']);
        Route::put('change-status/{subsidiary}', [SubsidiariesController::class, 'toggleSubsidiaryStatus']);
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UsersController::class, 'index']);
        Route::get('show/{user}', [UsersController::class, 'show']);
        Route::put('change-status/{user}', [UsersController::class, 'toggleSubsidiaryStatus']);
        Route::put('update-user/{user}', [ClientsController::class, 'updateClientUser']);

        Route::get('audit-trail', [UsersController::class, 'auditTrail']);
        
        Route::get('fetch-notifications', [UsersController::class, 'licenseNotifications']);
        Route::get('mark-notification-as-read/{notification}', [UsersController::class, 'markNotificationAsRead']);
        
    });
    

    
});
