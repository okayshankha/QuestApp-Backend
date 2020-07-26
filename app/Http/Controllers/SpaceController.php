<?php

namespace App\Http\Controllers;

use App\Notifications\InvitationToStudent;
use App\Rules\SpaceBelongsToUser;
use App\Rules\VerifyStudent;
use App\Space;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpaceController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Space Data
             */
            $request->merge(['space_id' => $id]);
            $request->validate([
                'space_id' => ['required', 'string', new SpaceBelongsToUser],
            ]);

            $space = Space::onlyTrashed()
                ->where('space_id', $id)
                ->first();
            if ($space) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $space;
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.no_records_found');
                $response['data']['message'] = 'No Trashed Records found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Trashed Space Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = Space::class;

            $spaces = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength,
                'trashOnly' => true
            ]);

            return ResponseHelper($spaces);
        }
    }


    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Space Data
             */
            $request->merge(['space_id' => $id]);
            $request->validate([
                'space_id' => ['required', 'string', new SpaceBelongsToUser],
            ]);

            $space = Space::where('space_id', $id)
                ->first();
            if ($space) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $space;
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.no_records_found');
                $response['data']['message'] = 'No Records found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Space Data
             */
            $request->validate([
                'pagelength' => 'integer',
                'page' => 'integer'
            ]);
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = Space::class;

            $spaces = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength
            ]);

            return ResponseHelper($spaces);
        }
    }

    function FindInvited(Request $request, $id = null)
    {
        $Model = Space::class;
        $userLevels = config('QuestApp.UserLevels');

        if ($id) {
            /**
             * Fetch Specific Space Data
             */
            $request->merge(['space_id' => $id]);
            $request->validate([
                'space_id' => ['required', 'string', 'exists:spaces,space_id'],
            ]);

            $modelClassTableName = (new $Model)->getTable();
            $modelClassIdString = $this->GetCustomClassIdString($Model);
            $type = (request()->user()->role === $userLevels['t']) ? 'teacher' : "";

            $space = Space::join('entity_user_mappings', "$modelClassTableName.$modelClassIdString", '=', 'entity_user_mappings.entity_id')
                ->where('entity_user_mappings.user_id', request()->user()->user_id)
                ->where('entity_user_mappings.active', true)
                ->where('entity_user_mappings.type', explode('_', $modelClassIdString)[0] . ":for_$type")
                ->where("$modelClassTableName.$modelClassIdString", $id)
                ->select("$modelClassTableName.*", 'entity_user_mappings.joined_at')
                ->first();
            if ($space) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $space;
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.no_records_found');
                $response['data']['message'] = 'No Records found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Space Data
             */
            $request->validate([
                'pagelength' => 'integer',
                'page' => 'integer'
            ]);
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = Space::class;

            $spaces = $this->FetchPagedRecordsWithJoinMapping($Model, [
                'page' => $page,
                'pagelength' => $pagelength
            ]);

            return ResponseHelper($spaces);
        }
    }


    function Create(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', new SpaceBelongsToUser],
            'description' => 'string'
        ]);

        $space = new Space([
            'name' => $request->name,
            'description' => $request->description,
            'created_by_user_id' => $request->user()->user_id,
        ]);

        $space->save();
        $space->space_id = sha1('Space' . $space->id);
        $space->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Space Created Successfully";
        return ResponseHelper($response);
    }


    function Delete(Request $request, $id)
    {
        $request->merge(['space_id' => $id]);
        $request->validate([
            'space_id' => ['required', 'string', 'exists:spaces,space_id', new SpaceBelongsToUser],
        ]);

        $space = Space::where('space_id', $id)->first();
        if ($space) {
            $space->deleted_by_user_id = $request->user()->user_id;
            $space->active = false;
            $space->save();
            $space->delete();
            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = "Space Deleted Successfully";
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Space found';
            return ResponseHelper($response);
        }
    }

    function Restore(Request $request, $id)
    {
        $request->merge(['space_id' => $id]);
        $request->validate([
            'space_id' => ['required', 'string', new SpaceBelongsToUser],
        ]);

        $space = Space::onlyTrashed()->where('space_id', $id)->first();
        if ($space) {
            $space->restore();
            $space->deleted_by_user_id = null;
            $space->save();
            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = "Space Restored Successfully";
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Space found';
            return ResponseHelper($response);
        }
    }

    function Update(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:spaces,space_id', new SpaceBelongsToUser],
            'field' => ['required', 'string', Rule::in(Space::getUpdatableFields())],
            'value' => 'required|string'
        ]);

        $space = Space::where('space_id', $request->id)->first();

        if ($space) {
            if ($request->field === 'active') {
                if (in_array($request->value, ['active', '1', 'inactive', '0'])) {
                    if (in_array($request->value, ['active', '1'])) {
                        $request->value = 1;
                    } elseif (in_array($request->value, ['inactive', '0'])) {
                        $request->value = 0;
                    }
                } else {
                    $response = config('QuestApp.JsonResponse.Unprocessable');
                    $response['data']['errors'] = [
                        "field" => [
                            "The active field value is invalid. It can be active/1 or inactive/0"
                        ]
                    ];
                    return ResponseHelper($response);
                }
            }
            $space->{$request->field} = $request->value;
            $space->modified_by_user_id = $request->user()->user_id;
            $space->save();

            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = 'Space has been updated';
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Space found';
            return ResponseHelper($response);
        }
    }


    function Invite(Request $request, $usertype = null, $resend = null)
    {
        $type = 'space';
        $Model = Space::class;
        return $this->SendInviteToEntity($request, $usertype, $type, $Model, $resend);
    }
}
