<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@Login');
    Route::post('register', 'AuthController@Register');
    Route::get('signup/activate/{token}', 'AuthController@signupActivate');

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('logout', 'AuthController@Logout');
        Route::get('user', 'AuthController@User');
    });
});


/**
 * Reset Password Routes
 */
Route::group(['prefix' => 'password', 'middleware' => 'auth:api'], function () {
    Route::post('create', 'PasswordResetController@create');
    Route::get('find/{token}', 'PasswordResetController@find');
    Route::post('reset', 'PasswordResetController@reset');
});


Route::group(['middleware' => 'auth:api'], function () {

    Route::group(['prefix' => 'faculty'], function () {
        // Fetch Faculty Data
        Route::get('/{id?}', 'FacultyController@Find');

        // Create New Faculty
        Route::post('/', 'FacultyController@Create')->middleware('admin.only');;
    });


    Route::group(['prefix' => 'department'], function () {

        // Update Department Data
        Route::put('/set', 'DepartmentController@Update')->middleware('admin.level');

        // Fetch Trashed Department Data
        Route::get('/trashed/{id?}', 'DepartmentController@FindTrashed')->middleware('admin.level');

        // Restore Trashed Department Data
        Route::get('/restore/{id}', 'DepartmentController@Restore')->middleware('admin.level');

        // Fetch Department Data
        Route::get('/{id?}', 'DepartmentController@Find');

        // Delete Department Data
        Route::delete('/{id}', 'DepartmentController@Delete')->middleware('admin.level');

        // Create New Department
        Route::post('/', 'DepartmentController@Create')->middleware('admin.level');
    });

    Route::group(['prefix' => 'category'], function () {
        // Update Category Data
        Route::put('/set', 'CategoryController@Update')->middleware('admin.level');

        // Fetch Trashed Category Data
        Route::get('/trashed/{id?}', 'CategoryController@FindTrashed')->middleware('admin.level');

        // Restore Trashed Category Data
        Route::get('/restore/{id}', 'CategoryController@Restore')->middleware('admin.level');

        // Fetch Category Data
        Route::get('/{id?}', 'CategoryController@Find');

        // Delete Category Data
        Route::delete('/{id}', 'CategoryController@Delete')->middleware('admin.level');

        // Create New Category
        Route::post('/', 'CategoryController@Create')->middleware('admin.level');
    });
});


Route::fallback(function () {
    $response = config('QuestApp.JsonResponse.404');
    $response['data']['message'] = 'Route Not Found';
    return ResponseHelper($response);
});
