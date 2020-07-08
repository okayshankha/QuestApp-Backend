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

Route::get('avatar/{user_sl}/{filename}', 'AuthController@GetAvatar');

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
Route::post('create', 'PasswordResetController@create');
Route::group(['prefix' => 'password', 'middleware' => 'auth:api'], function () {
    Route::get('find/{token}', 'PasswordResetController@find');
    Route::post('reset', 'PasswordResetController@reset');
});


Route::group(['middleware' => 'auth:api'], function () {

    Route::group(['prefix' => 'faculty'], function () {

        // Fetch Faculty Data
        Route::get('/{id?}', 'FacultyController@Find');

        // Create New Faculty
        Route::post('/', 'FacultyController@Create')->middleware('admin.only');
    });

    Route::group(['prefix' => 'department'], function () {

        // Update Department Data
        Route::put('/', 'DepartmentController@Update')->middleware('admin.level');

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
        Route::put('/', 'CategoryController@Update')->middleware('admin.level');

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

    Route::group(['prefix' => 'subject'], function () {

        // Update Subject Data
        Route::put('/', 'SubjectController@Update')->middleware('admin.level');

        // Fetch Trashed Subject Data
        Route::get('/trashed/{id?}', 'SubjectController@FindTrashed')->middleware('admin.level');

        // Restore Trashed Subject Data
        Route::get('/restore/{id}', 'SubjectController@Restore')->middleware('admin.level');

        // Fetch Subject Data
        Route::get('/{id?}', 'SubjectController@Find');

        // Delete Subject Data
        Route::delete('/{id}', 'SubjectController@Delete')->middleware('admin.level');

        // Create New Subject
        Route::post('/', 'SubjectController@Create')->middleware('admin.level');
    });

    Route::group(['prefix' => 'examination'], function () {
        
        // Map Questions with Examination
        Route::post('/addquestions', 'ExaminationController@MapQuestions')->middleware('admin.level');

        // Get Mapped Questions
        Route::get('/questions/{id}', 'ExaminationController@GetMappedQuestions')->middleware('admin.level');

        // Update Subject Data
        Route::put('/', 'ExaminationController@Update')->middleware('admin.level');

        // Fetch Trashed Subject Data
        Route::get('/trashed/{id?}', 'ExaminationController@FindTrashed')->middleware('admin.level');

        // Restore Trashed Subject Data
        Route::get('/restore/{id}', 'ExaminationController@Restore')->middleware('admin.level');

        // Fetch Subject Data
        Route::get('/{id?}', 'ExaminationController@Find');

        // Delete Subject Data
        Route::delete('/{id}', 'ExaminationController@Delete')->middleware('admin.level');

        // Create New Subject
        Route::post('/', 'ExaminationController@Create')->middleware('admin.level');
    });

    Route::group(['prefix' => 'question'], function () {

        // Update Subject Data
        Route::put('/', 'QuestionController@Update')->middleware('admin.level');

        // Fetch Trashed Subject Data
        Route::get('/trashed/{id?}', 'QuestionController@FindTrashed')->middleware('admin.level');

        // Restore Trashed Subject Data
        Route::get('/restore/{id}', 'QuestionController@Restore')->middleware('admin.level');

        // Fetch Subject Data
        Route::get('/{id?}', 'QuestionController@Find');

        // Delete Subject Data
        Route::delete('/{id}', 'QuestionController@Delete')->middleware('admin.level');

        // Create New Subject
        Route::post('/', 'QuestionController@Create')->middleware('admin.level');
    });

    Route::group(['prefix' => 'stats'], function () {
        // Fetch dashboard
        Route::get('/dashboard/overview', 'Statistics@DashboardOverview');
    });
});


Route::fallback(function () {
    $response = config('QuestApp.JsonResponse.404');
    $response['data']['message'] = 'Route Not Found';
    return ResponseHelper($response);
});
