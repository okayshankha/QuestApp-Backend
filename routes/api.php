<?php

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

    // Route::group(['prefix' => 'hod'], function () {

    //     // Fetch Faculty Data
    //     Route::get('/{id?}', 'UserController@FindHod');

    //     // Create New Faculty
    //     Route::post('/', 'UserController@CreateHod')->middleware('admin.scope');
    // });

    Route::group(['prefix' => 'faculty'], function () {

        // Fetch Trashed Faculty Data
        Route::get('/trashed/{id?}', 'UserController@FindTrashedFaculty')->middleware('super.admin.scope');

        // Fetch Faculty Data
        Route::get('/{id?}', 'UserController@FindTeachers')->middleware('super.admin.scope');

        // Create New Faculty
        Route::post('/', 'UserController@CreateFaculty')->middleware('admin.scope');
    });

    Route::group(['prefix' => 'student'], function () {

        // Fetch Trashed Student Data
        Route::get('/trashed/{id?}', 'UserController@FindTrashedStudent');

        // Fetch Student Data
        Route::get('/{id?}', 'UserController@FindStudent');

        // Create New Student
        Route::post('/', 'UserController@CreateStudent')->middleware('admin.scope');
    });




    Route::group(['prefix' => 'space'], function () {

        // Update Department Data
        Route::put('/', 'SpaceController@Update')->middleware('admin.scope');

        // Fetch Trashed Department Data
        Route::get('/trashed/{id?}', 'SpaceController@FindTrashed')->middleware('admin.scope');

        // Restore Trashed Department Data
        Route::get('/restore/{id}', 'SpaceController@Restore')->middleware('admin.scope');

        // Fetch Department Data
        Route::get('/{id?}', 'SpaceController@Find');

        // Delete Department Data
        Route::delete('/{id}', 'SpaceController@Delete')->middleware('admin.scope');

        // Create New Department
        Route::post('/', 'SpaceController@Create')->middleware('admin.scope');
    });

    Route::group(['prefix' => 'class'], function () {
        // Update Category Data
        Route::put('/', 'ClassController@Update')->middleware('admin.scope');

        // Fetch Trashed Category Data
        Route::get('/trashed/{id?}', 'ClassController@FindTrashed')->middleware('admin.scope');

        // Restore Trashed Category Data
        Route::get('/restore/{id}', 'ClassController@Restore')->middleware('admin.scope');

        // Fetch Category Data
        Route::get('/{id?}', 'ClassController@Find');

        // Delete Category Data
        Route::delete('/{id}', 'ClassController@Delete')->middleware('admin.scope');

        // Create New Category
        Route::post('/', 'ClassController@Create')->middleware('admin.scope');
    });

    Route::group(['prefix' => 'subject'], function () {

        // Update Subject Data
        Route::put('/', 'SubjectController@Update')->middleware('admin.scope');

        // Fetch Trashed Subject Data
        Route::get('/trashed/{id?}', 'SubjectController@FindTrashed')->middleware('admin.scope');

        // Restore Trashed Subject Data
        Route::get('/restore/{id}', 'SubjectController@Restore')->middleware('admin.scope');

        // Fetch Subject Data
        Route::get('/{id?}', 'SubjectController@Find');

        // Delete Subject Data
        Route::delete('/{id}', 'SubjectController@Delete')->middleware('admin.scope');

        // Create New Subject
        Route::post('/', 'SubjectController@Create')->middleware('admin.scope');
    });

    Route::group(['prefix' => 'examination'], function () {

        // Map Questions with Examination
        Route::post('/addquestions', 'ExaminationController@MapQuestions')->middleware('admin.scope');

        // Get Mapped Questions
        Route::get('/questions/{id}', 'ExaminationController@GetMappedQuestions')->middleware('admin.scope');

        // Update Subject Data
        Route::put('/', 'ExaminationController@Update')->middleware('admin.scope');

        // Fetch Trashed Subject Data
        Route::get('/trashed/{id?}', 'ExaminationController@FindTrashed')->middleware('admin.scope');

        // Restore Trashed Subject Data
        Route::get('/restore/{id}', 'ExaminationController@Restore')->middleware('admin.scope');

        // Fetch Subject Data
        Route::get('/{id?}', 'ExaminationController@Find');

        // Delete Subject Data
        Route::delete('/{id}', 'ExaminationController@Delete')->middleware('admin.scope');

        // Create New Subject
        Route::post('/', 'ExaminationController@Create')->middleware('admin.scope');
    });

    Route::group(['prefix' => 'question'], function () {

        // Update Subject Data
        Route::put('/', 'QuestionController@Update')->middleware('admin.scope');

        // Fetch Trashed Subject Data
        Route::get('/trashed/{id?}', 'QuestionController@FindTrashed')->middleware('admin.scope');

        // Restore Trashed Subject Data
        Route::get('/restore/{id}', 'QuestionController@Restore')->middleware('admin.scope');

        // Fetch Subject Data
        Route::get('/{id?}', 'QuestionController@Find');

        // Delete Subject Data
        Route::delete('/{id}', 'QuestionController@Delete')->middleware('admin.scope');

        // Create New Subject
        Route::post('/', 'QuestionController@Create')->middleware('admin.scope');
    });

    Route::group(['prefix' => 'stats'], function () {
        // Fetch dashboard
        Route::get('/dashboard/overview', 'Statistics@DashboardOverview');
    });
});

Route::get('/ping', function(){
    $response = config('QuestApp.JsonResponse.success');
    return ResponseHelper($response);
});


Route::fallback(function () {
    $response = config('QuestApp.JsonResponse.404');
    $response['data']['message'] = 'Route Not Found';
    return ResponseHelper($response);
});
