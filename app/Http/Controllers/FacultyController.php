<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Carbon\Carbon;
use Avatar;
use Storage;

// Models
use App\User;

// Notifications
use App\Notifications\SignupActivate;


use Illuminate\Http\Request;

class FacultyController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Faculty Data
             */
            $faculty = User::onlyTrashed()->where('user_id', $id)
                ->where('role', 'faculty')
                ->first();
            if ($faculty) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $faculty,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Record found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Trashed Faculty Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            //New//

            $total = User::onlyTrashed()->where('role', 'faculty')->count();

            $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

            $pagelength =  $CalculatePaginationData['pagelength'];
            $offset = $CalculatePaginationData['offset'];
            $hasNext = $CalculatePaginationData['hasNext'];
            $totalpagecount = $CalculatePaginationData['totalpagecount'];
            $currentpagecount = $CalculatePaginationData['currentpagecount'];

            $records = User::onlyTrashed()->where('role', 'faculty')->skip($offset)->take($pagelength)->get();

            if ($offset > 0) {
                $_records = [];
                foreach ($records as $record) {
                    $_records[] = $record;
                }
                $records = $_records;
            }

            $response = null;
            if ($records->count() > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'hasnext' => $hasNext,
                    'currentpagecount' => $currentpagecount,
                    'totalpagecount' => $totalpagecount,
                    'records' => $records,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = "Faculty not found";
            }
            return ResponseHelper($response);




            ////Old////
            /*




            if (!$pagelength) $paginate = 10;
            if (!$page) $page = 0;
            if ($page == 1) $page = 0;
            $offset = (int) $paginate * $page;


            $total = User::where('role', 'faculty')->count();
            $hasNext = ($total - ($offset + $paginate)) > 0;
            $faculties = User::where('role', 'faculty')->skip($offset)->take($paginate)->get();
            $response = null;
            if ($faculties->count() > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'hasnext' => $hasNext,
                    'faculties' => $faculties,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = "Faculty not found";
            }
            return ResponseHelper($response);

            */
        }
    }


    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Faculty Data
             */
            $faculty = User::where('user_id', $id)
                ->where('role', 'faculty')
                ->first();
            if ($faculty) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $faculty,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Record found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Faculty Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            //New//

            $total = User::where('role', 'faculty')->count();

            $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

            $pagelength =  $CalculatePaginationData['pagelength'];
            $offset = $CalculatePaginationData['offset'];
            $hasNext = $CalculatePaginationData['hasNext'];
            $totalpagecount = $CalculatePaginationData['totalpagecount'];
            $currentpagecount = $CalculatePaginationData['currentpagecount'];

            $records = User::where('role', 'faculty')->skip($offset)->take($pagelength)->get();

            if ($offset > 0) {
                $_records = [];
                foreach ($records as $record) {
                    $_records[] = $record;
                }
                $records = $_records;
            }

            $response = null;
            if ($records->count() > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'hasnext' => $hasNext,
                    'currentpagecount' => $currentpagecount,
                    'totalpagecount' => $totalpagecount,
                    'records' => $records,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = "Faculty not found";
            }
            return ResponseHelper($response);




            ////Old////
            /*




            if (!$pagelength) $paginate = 10;
            if (!$page) $page = 0;
            if ($page == 1) $page = 0;
            $offset = (int) $paginate * $page;


            $total = User::where('role', 'faculty')->count();
            $hasNext = ($total - ($offset + $paginate)) > 0;
            $faculties = User::where('role', 'faculty')->skip($offset)->take($paginate)->get();
            $response = null;
            if ($faculties->count() > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'hasnext' => $hasNext,
                    'faculties' => $faculties,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = "Faculty not found";
            }
            return ResponseHelper($response);

            */
        }
    }


    function Create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
        ]);

        $password = 'Hello@123';

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($password),
            'activation_token' => Str::random(60),
            'role' => 'faculty'
        ]);

        $user->save();
        $user->user_id = sha1('User' . $user->id);
        $user->save();

        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/' . $user->id . '/avatar.png', (string) $avatar);

        $user->notify(new SignupActivate($user));

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Faculty Created Successfully";
        return ResponseHelper($response);
    }
}
