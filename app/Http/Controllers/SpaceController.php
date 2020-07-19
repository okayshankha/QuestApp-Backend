<?php

namespace App\Http\Controllers;


use App\Space;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SpaceController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Space Data
             */
            $space = Space::onlyTrashed()
                ->where('space_id', $id)
                ->first();
            if ($space) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $space;
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Trashed Record found';
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
            $space = Space::where('space_id', $id)
                ->first();
            if ($space) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $space;
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Record found';
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


    function Create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:spaces',
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
        $validator = Validator::make(
            ['space_id' => $id],
            ['space_id' => 'required|exists:spaces,space_id']
        );

        if ($validator) {
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
    }

    function Restore(Request $request, $id)
    {
        $validator = Validator::make(
            ['space_id' => $id],
            ['space_id' => 'required|exists:spaces,space_id']
        );

        if ($validator) {
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
    }

    function Update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:spaces,space_id',
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
}
