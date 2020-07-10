<?php

namespace App\Http\Controllers;

use App\Department;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Avatar;
use Storage;

// Models
use App\User;

// Notifications
use App\Notifications\SignupActivate;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function FindAdmin(Request $request, $id = null)
    {
        return $this->Find($request, $id, 'a');
    }

    function FindHod(Request $request, $id = null)
    {
        $userLevels = config('QuestApp.UserLevels');
        if ($id) {
            /**
             * Fetch Specific User Data
             */
            $validator = Validator::make(
                ['id' => $id],
                ['id' => 'required|exists:users,user_id']
            )->validate();

            if ($validator) {
                $records = Department::where('hod_user_id', $id)->get();

                if ($records->count() > 0) {
                    $response = config('QuestApp.JsonResponse.success');
                    $response['data']['message'] = [
                        'record' => array_merge($request->user()->toArray(), ['departments' => $records]),
                    ];
                } else {
                    $response = config('QuestApp.JsonResponse.404');
                    $response['data']['message'] = "{$userLevels['h']} not found";
                }
                return ResponseHelper($response);
            }
        } else {
            $hod_user_ids = Department::all()->pluck('hod_user_id')->toArray();

            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $total = count($hod_user_ids);

            $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

            $pagelength =  $CalculatePaginationData['pagelength'];
            $offset = $CalculatePaginationData['offset'];
            $hasNext = $CalculatePaginationData['hasNext'];
            $totalpagecount = $CalculatePaginationData['totalpagecount'];
            $currentpagecount = $CalculatePaginationData['currentpagecount'];


            

            $records = [];
            for ($i = $offset; $i < $pagelength && $i < count($hod_user_ids); $i++) {
                $hod_user_id = $hod_user_ids[$i];
                if ($hod_user_id != null || trim($hod_user_id) != '') {
                    $records[] = array_merge(
                        User::where('user_id', $hod_user_id)->first()->toArray(),
                        [
                            'departments' => Department::where('hod_user_id', $hod_user_id)->get()->toArray()
                        ]
                    );
                }
            }

            if (count($records) > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'hasnext' => $hasNext,
                    'currentpagecount' => $currentpagecount,
                    'totalpagecount' => $totalpagecount,
                    'records' => $records,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = "Hod not found";
            }
            return ResponseHelper($response);
        }
    }

    function FindFaculty(Request $request, $id = null)
    {
        return $this->Find($request, $id, 'f');
    }

    function FindStudent(Request $request, $id = null)
    {
        return $this->Find($request, $id, 's');
    }



    // Find Trashed

    function FindTrashedAdmin(Request $request, $id = null)
    {
        return $this->Find($request, $id, 'a', true);
    }

    function FindTrashedFaculty(Request $request, $id = null)
    {
        return $this->Find($request, $id, 'f', true);
    }

    function FindTrashedStudent(Request $request, $id = null)
    {
        return $this->Find($request, $id, 's', true);
    }



    // Create

    function CreateAdmin(Request $request)
    {
        return $this->Create($request, 'a');
    }

    function CreateHod(Request $request)
    {
        $userLevels = config('QuestApp.UserLevels');
        $request->validate([
            'department_id' => 'required|string|exists:departments,department_id',
            'user_id' => 'required|string|exists:users,user_id',
        ]);


        $department = Department::where('department_id', $request->department_id)->first();
        $department->hod_user_id = $request->user()->user_id;
        $department->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "{$userLevels['h']} Created Successfully";
        return ResponseHelper($response);
    }

    function CreateFaculty(Request $request)
    {
        return $this->Create($request, 'f');
    }

    function CreateStudent(Request $request)
    {
        return $this->Create($request, 's');
    }






    /**
     * Common Functions
     */
    private function Create(Request $request, $type)
    {
        $userLevels = config('QuestApp.UserLevels');
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
        ]);

        $password = 'Hello@123';

        $user = new User([
            'user_id' => 'TempIDFaculty' . $request->email,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($password),
            'activation_token' => Str::random(60),
            'role' => $userLevels[$type]
        ]);

        $user->save();
        $user->user_id = sha1('UserFaculty' . $user->id);
        $user->save();

        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/' . $user->id . '/avatar.png', (string) $avatar);

        $user->notify(new SignupActivate($user));

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "{$userLevels[$type]} Created Successfully";
        return ResponseHelper($response);
    }

    private function Find(Request $request, $id = null, $type, $filterTrashed = false)
    {
        $userLevels = config('QuestApp.UserLevels');
        if ($id) {
            /**
             * Fetch Specific User Data
             */
            $validator = Validator::make(
                ['id' => $id],
                ['id' => 'required|exists:users,user_id']
            )->validate();


            if ($validator) {
                $record = null;

                if ($filterTrashed) {
                    $record = User::onlyTrashed()
                        ->where('user_id', $id)
                        ->where('role',  $userLevels[$type])
                        ->first();
                } else {
                    $record = User::where('user_id', $id)
                        ->where('role',  $userLevels[$type])
                        ->first();
                }

                if ($record) {
                    $response = config('QuestApp.JsonResponse.success');
                    $response['data']['message'] = [
                        'record' => $record,
                    ];
                    return ResponseHelper($response);
                }
            }
        } else {
            /**
             * Fetch All User Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $total = User::where('role',  $userLevels[$type])->count();

            $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

            $pagelength =  $CalculatePaginationData['pagelength'];
            $offset = $CalculatePaginationData['offset'];
            $hasNext = $CalculatePaginationData['hasNext'];
            $totalpagecount = $CalculatePaginationData['totalpagecount'];
            $currentpagecount = $CalculatePaginationData['currentpagecount'];

            $records = [];

            if ($filterTrashed) {
                $records = User::onlyTrashed()
                    ->where('role', $userLevels[$type])
                    ->skip($offset)
                    ->take($pagelength)
                    ->get();
            } else {
                $records = User::where('role', $userLevels[$type])
                    ->skip($offset)
                    ->take($pagelength)
                    ->get();
            }

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
                $response['data']['message'] = "{$userLevels[$type]} not found";
            }
            return ResponseHelper($response);
        }
    }

    // private function FindTrashed(Request $request, $id = null, $type)
    // {
    //     $userLevels = config('QuestApp.UserLevels');
    //     if ($id) {
    //         /**
    //          * Fetch Specific Trashed User Data
    //          */
    //         $record = User::onlyTrashed()->where('user_id', $id)
    //             ->where('role', $userLevels[$type])
    //             ->first();
    //         if ($record) {
    //             $response = config('QuestApp.JsonResponse.success');
    //             $response['data']['message'] = [
    //                 'record' => $record,
    //             ];
    //             return ResponseHelper($response);
    //         } else {
    //             $response = config('QuestApp.JsonResponse.404');
    //             $response['data']['message'] = 'No Record found';
    //             return ResponseHelper($response);
    //         }
    //     } else {
    //         /**
    //          * Fetch All Trashed User Data
    //          */
    //         $pagelength = $request->query('pagelength');
    //         $page = $request->query('page');

    //         $total = User::onlyTrashed()->where('role', $userLevels[$type])->count();

    //         $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

    //         $pagelength =  $CalculatePaginationData['pagelength'];
    //         $offset = $CalculatePaginationData['offset'];
    //         $hasNext = $CalculatePaginationData['hasNext'];
    //         $totalpagecount = $CalculatePaginationData['totalpagecount'];
    //         $currentpagecount = $CalculatePaginationData['currentpagecount'];

    //         $records = User::onlyTrashed()->where('role', 'faculty')->skip($offset)->take($pagelength)->get();

    //         if ($offset > 0) {
    //             $_records = [];
    //             foreach ($records as $record) {
    //                 $_records[] = $record;
    //             }
    //             $records = $_records;
    //         }

    //         $response = null;
    //         if ($records->count() > 0) {
    //             $response = config('QuestApp.JsonResponse.success');
    //             $response['data']['message'] = [
    //                 'hasnext' => $hasNext,
    //                 'currentpagecount' => $currentpagecount,
    //                 'totalpagecount' => $totalpagecount,
    //                 'records' => $records,
    //             ];
    //         } else {
    //             $response = config('QuestApp.JsonResponse.404');
    //             $response['data']['message'] = "Faculty not found";
    //         }
    //         return ResponseHelper($response);
    //     }
    // }
}
