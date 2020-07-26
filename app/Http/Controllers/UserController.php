<?php

namespace App\Http\Controllers;

use App\Department;
use App\EntityUserMapping;
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
    function FindTeachers(Request $request, $id = null)
    {
        return $this->Find($request, $id, 't');
    }

    function FindStudent(Request $request, $id = null)
    {
        return $this->Find($request, $id, 's');
    }


    // Find Trashed
    function FindTrashedTeachers(Request $request, $id = null)
    {
        return $this->Find($request, $id, 't', true);
    }

    function FindTrashedStudent(Request $request, $id = null)
    {
        return $this->Find($request, $id, 's', true);
    }


    // Create
    function CreateTeacher(Request $request)
    {
        return $this->Create($request, 't');
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
            'user_id' => sha1('User' . $request->email . Str::random(60)),
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($password),
            'activation_token' => Str::random(60),
            'role' => $userLevels[$type]
        ]);

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
                    $response['data']['message'] = 'Records Fetched successfully';
                    $response['data']['result'] = $record;

                    return ResponseHelper($response);
                }
            }
        } else {
            /**
             * Fetch All User Data (Trashed or Active)
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


            $records = $records->toArray();


            foreach ($records as $record) {
                unset($record['avatar']);
                if ($request->user()->role === $userLevels['sa'] || $request->user()->role !== $userLevels['t']) {
                    unset($record['email']);
                    unset($record['email_verified_at']);
                    unset($record['role']);
                }
            }

            $response = null;
            if (count($records) > 0) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched successfully';
                $response['data']['result'] = [
                    'hasnext' => $hasNext,
                    'currentpagecount' => $currentpagecount,
                    'totalpagecount' => $totalpagecount,
                    'records' => $records,
                ];
            } else {
                $response = config('QuestApp.JsonResponse.no_records_found');
                $response['data']['message'] = "No Records found";
            }
            return ResponseHelper($response);
        }
    }

    function Join(Request $request, $activation_token)
    {
        $response = null;
        $user = null;
        $userLevels = config('QuestApp.UserLevels');

        $entitymap = EntityUserMapping::where('activation_token', $activation_token)->first();

        if ($entitymap) {
            if (filter_var($entitymap->user_id, FILTER_VALIDATE_EMAIL)) {
                if ($entitymap->type == 'class:for_student') {
                    $user = User::where('email', $entitymap->user_id)
                        ->where('role', $userLevels['s'])
                        ->first();
                } else {
                    $user = User::where('email', $entitymap->user_id)
                        ->where('role', $userLevels['t'])
                        ->first();
                }
            } else {
                if ($entitymap->type == 'class:for_student') {
                    $user = User::where('user_id', $entitymap->user_id)
                        ->where('role', $userLevels['s'])
                        ->first();
                } else {
                    $user = User::where('user_id', $entitymap->user_id)
                        ->where('role', $userLevels['t'])
                        ->first();
                }
            }

            if ($user) {
                $response = config('QuestApp.JsonResponse.success');
                if (!$entitymap->active) {
                    $entitymap->user_id = $user->user_id;
                    $entitymap->joined_at = Carbon::now()->toISOString();
                    $response['data']['message'] = "Joined Successfully";
                } else {
                    $response['data']['message'] = "Already Joined.";
                }
                $entitymap->active = true;
                $entitymap->activation_token = null;
                $entitymap->save();
            } else {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Email is not registered.";
            }
        } else {
            $response = config('QuestApp.JsonResponse.no_records_found');
            $response['data']['message'] = "Invalid Join Activation Link.";
        }

        return ResponseHelper($response);
    }
}
