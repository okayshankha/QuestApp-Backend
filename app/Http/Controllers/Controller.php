<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use App\EntityUserMapping;
use App\MyClass;
use App\Notifications\InvitationToStudent;
use App\Rules\ClassBelongsToUser;
use App\Rules\ExceptSelf;
use App\Rules\SpaceBelongsToUser;
use App\Rules\VerifyStudent;
use App\Rules\VerifyTeacher;
use Illuminate\Validation\Rule;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function CalculatePaginationData($total, $page, $pagelength)
    {
        $defaults = [
            'pagelength' => 5,
            'page' => 1,
            'totalpagecount' => 0,
            'currentpagecount' => 1
        ];


        $output = [
            'pagelength' => 0,
            'offset' => 0,
            'hasNext' => 0,
        ];

        // Set Default Values
        if (!$pagelength) {
            $pagelength = $defaults['pagelength'];
        } else {
            $pagelength = (int) $pagelength;
            if ($pagelength <= 0) {
                $pagelength = $defaults['pagelength'];
            }
        }

        if (!$page) {
            $page = $defaults['page'];
        } else {
            $page = (int) $page;
            if ($page <= 0) {
                $page = $defaults['page'];
            }
        }


        $offset = $pagelength * ($page - 1);

        $output['pagelength'] = $pagelength;
        $output['offset'] = $offset;
        $output['hasNext'] = (($offset + $pagelength) >= $total) ? 0 : 1;
        $output['totalpagecount'] = (($total % $pagelength) <= 0) ? (int) ($total / $pagelength) : ((int) ($total / $pagelength) + 1);
        $output['currentpagecount'] = $page;



        return $output;
    }

    protected function FetchPagedRecords($Model, $options)
    {
        $page = array_key_exists('page', $options) ? $options['page'] : null;
        $pagelength = array_key_exists('pagelength', $options) ? $options['pagelength'] : null;
        $trashOnly = array_key_exists('trashOnly', $options) ? $options['trashOnly'] : false;
        $total = array_key_exists('total', $options) ? $options['total'] : null;


        if (!$trashOnly) {
            $total = $Model::all()->count();
        } else {
            $total = $Model::onlyTrashed()->count();
        }

        $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

        $pagelength =  $CalculatePaginationData['pagelength'];
        $offset = $CalculatePaginationData['offset'];
        $hasNext = $CalculatePaginationData['hasNext'];
        $totalpagecount = $CalculatePaginationData['totalpagecount'];
        $currentpagecount = $CalculatePaginationData['currentpagecount'];

        $records = null;
        if (!$trashOnly) {
            $records = $Model::all()
                ->where('created_by_user_id', request()->user()->user_id)
                ->skip($offset)
                ->take($pagelength);
        } else {
            $records = $Model::onlyTrashed()
                ->where('created_by_user_id', request()->user()->user_id)
                ->get()
                ->skip($offset)
                ->take($pagelength);
        }

        if ($offset >= 0) {
            $_records = [];
            foreach ($records as $record) {
                $_records[] = $record;
            }
            $records = $_records;
        }

        $response = null;
        if (!count($records)) {
            $response = config('QuestApp.JsonResponse.no_records_found');
            if ($trashOnly) {
                $response['data']['message'] = "No Trashed Records found";
            }
        } else {
            $response = config('QuestApp.JsonResponse.success');

            $response['data']['message'] = 'Records Fetched Successfully';
            $response['data']['result'] = [
                'hasnext' => $hasNext,
                'currentpagecount' => $currentpagecount,
                'totalpagecount' => $totalpagecount,
                'records' => $records,
            ];
        }

        return $response;
    }

    protected function GetCustomClassIdString($Model)
    {
        $modelClass = explode('\\', get_class(new $Model))[1];
        $modelClassIdString = '_id';

        switch ($modelClass) {
            case 'MyClass':
                $modelClassIdString = 'class' . $modelClassIdString;
                break;
            case 'Space':
                $modelClassIdString = 'space' . $modelClassIdString;
                break;
            default:
                $modelClassIdString = "id";
        }

        return $modelClassIdString;
    }


    protected function FetchPagedRecordsWithJoinMapping($Model, $options)
    {
        $userLevels = config('QuestApp.UserLevels');

        $page = array_key_exists('page', $options) ? $options['page'] : null;
        $pagelength = array_key_exists('pagelength', $options) ? $options['pagelength'] : null;
        $trashOnly = array_key_exists('trashOnly', $options) ? $options['trashOnly'] : false;
        $total = array_key_exists('total', $options) ? $options['total'] : null;

        $trashOnly = false;

        $modelClassTableName = (new $Model)->getTable();
        $modelClassIdString = $this->GetCustomClassIdString($Model);

        if (!$trashOnly) {
            $total = $Model::join('entity_user_mappings', "$modelClassTableName.$modelClassIdString", '=', 'entity_user_mappings.entity_id')
                ->where('entity_user_mappings.active', true)
                ->where('entity_user_mappings.user_id', request()->user()->user_id)
                ->get()
                ->count();

            // dd($total);
        } else {
            $total = $Model::onlyTrashed()->count();
        }

        $CalculatePaginationData = $this->CalculatePaginationData($total, $page, $pagelength);

        $pagelength =  $CalculatePaginationData['pagelength'];
        $offset = $CalculatePaginationData['offset'];
        $hasNext = $CalculatePaginationData['hasNext'];
        $totalpagecount = $CalculatePaginationData['totalpagecount'];
        $currentpagecount = $CalculatePaginationData['currentpagecount'];

        $type = (request()->user()->role === $userLevels['s']) ? 'student' : 'teacher';

        $records = null;
        if (!$trashOnly) {
            $records = $Model::join('entity_user_mappings', "$modelClassTableName.$modelClassIdString", '=', 'entity_user_mappings.entity_id')
                ->where('entity_user_mappings.user_id', request()->user()->user_id)
                ->where('entity_user_mappings.active', true)
                ->where('entity_user_mappings.type', explode('_', $modelClassIdString)[0] . ":for_$type")
                ->select("$modelClassTableName.*", 'entity_user_mappings.joined_at')
                ->get()
                ->skip($offset)
                ->take($pagelength);
        } else {
            $records = $Model::onlyTrashed()
                ->where('created_by_user_id', request()->user()->user_id)
                ->get()
                ->skip($offset)
                ->take($pagelength);
        }

        if ($offset >= 0) {
            $_records = [];
            foreach ($records as $record) {
                $_records[] = $record;
            }
            $records = $_records;
        }

        $response = null;
        if (!count($records)) {
            $response = config('QuestApp.JsonResponse.no_records_found');
            if ($trashOnly) {
                $response['data']['message'] = "No Trashed Records found";
            }
        } else {
            $response = config('QuestApp.JsonResponse.success');

            $response['data']['message'] = 'Records Fetched Successfully';
            $response['data']['result'] = [
                'hasnext' => $hasNext,
                'currentpagecount' => $currentpagecount,
                'totalpagecount' => $totalpagecount,
                'records' => $records,
            ];
        }

        return $response;
    }


    protected function SendInviteToEntity(Request $request, $usertype, $type, $Model,  $resend = null)
    {

        $request->merge([
            'usertype' => $usertype,
            'resend' => $resend,
        ]);

        $modelClassTableName = (new $Model)->getTable();
        $modelClassIdString = $this->GetCustomClassIdString($Model);

        $id_validation_rule = [];
        $email_validation_rule = [];

        if ($usertype !== 'teacher') {
            $id_validation_rule = ['required_without:email', 'exists:users,user_id', new VerifyStudent];
            $email_validation_rule = ['required_without:id', 'email', new VerifyStudent('PASS_FOR_NON_EXISTENT'), new ExceptSelf];
        } else {
            $id_validation_rule = ['required_without:email', 'exists:users,user_id', new VerifyTeacher];
            $email_validation_rule = ['required_without:id', 'email', new VerifyTeacher('PASS_FOR_NON_EXISTENT'), new ExceptSelf];
        }

        $validation_rules = [
            'id' => $id_validation_rule,
            'email' => $email_validation_rule,
            'usertype' => ['required', Rule::in(['student', 'teacher'])],
            'resend' => Rule::in([null, 'resend', 'r'])
        ];


        if ($type === 'space') {
            $validation_rules['space_id'] = ['required', "exists:$modelClassTableName,$modelClassIdString", new SpaceBelongsToUser];
        } else if ($type === 'class') {
            $validation_rules['class_id'] = ['required', "exists:$modelClassTableName,$modelClassIdString", new ClassBelongsToUser];
        }


        $request->validate($validation_rules);

        $recipient = null;
        $user_id = null;

        $user_id = $request->id ? $request->id : $request->email;
        $entity_id = ($type === 'space') ? $request->space_id : $request->class_id;
        $entitymap = EntityUserMapping::where('user_id', $user_id)
            ->where('entity_id', $entity_id)
            ->first();

        if ($entitymap) {
            $resend = $resend ? true : false;
            $resend &= ($entitymap->active == false);
        }

        if ($entitymap && !$resend) {
            /**
             * If found records for entity map with user_id or email along with entity_id.
             * Means, Invitation has already been sent.
             */
            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = "Invitation has already been sent.";
            return ResponseHelper($response);
        } else if (!$entitymap) {
            /**
             * If no records found, then it might be the case where the user accepted the invitation,
             * and the email id has been replaced with user_id
             */
            if (filter_var($user_id, FILTER_VALIDATE_EMAIL)) {
                $recipient = User::where('email', $request->email)->first();
                if ($recipient) {
                    $user_id = $recipient->user_id;
                    $entitymap = EntityUserMapping::where('user_id', $recipient->user_id)
                        ->where('entity_id', $entity_id)
                        ->first();
                    if ($entitymap && !$resend) {
                        $response = config('QuestApp.JsonResponse.success');
                        $response['data']['message'] = "Invitation has already been sent.";
                        return ResponseHelper($response);
                    }
                }
            }
        }

        if (!$recipient) {
            $recipient = new User([
                'email' => $request->email,
                'name' => explode('@', $user_id)[0]
            ]);
        }

        if (!$entitymap) {
            $entitymap = new EntityUserMapping([
                'user_id' => $user_id,
                'entity_id' => $entity_id,
                'type' => "$type:for_$usertype",
                'created_by_user_id' => $request->user()->user_id,
            ]);
            $entitymap->save();
            $entitymap->entity_user_mapping_id = sha1('EntityUserMapping' . $entitymap->id);
        }


        /**
         * Format of the activation_token,
         * 
         * @class@class_id.entity_mapping_id.sender_id.random_string
         * or
         * @space@space_id.entity_mapping_id.sender_id.random_string
         */
        if ($entitymap->activation_token === null) {
            $entitymap->activation_token =
                "@$type@" . $request->class_id .
                $entitymap->entity_user_mapping_id .
                '.' . $request->user()->user_id .
                '.' . Str::random(50);
            $entitymap->save();
        }


        $payload['type'] = "space";
        $payload['payload'] = $Model::where($modelClassIdString, $entity_id)->first();
        $payload['join_url'] = "/api/join/{$entitymap->activation_token}";

        // dd($student);
        $recipient->notify(new InvitationToStudent($recipient, $payload));

        $response = config('QuestApp.JsonResponse.success');
        $response['data']['message'] = "Invitation Sent Successfully.";
        return ResponseHelper($response);
    }
}
