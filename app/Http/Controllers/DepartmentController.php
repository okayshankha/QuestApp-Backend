<?php

namespace App\Http\Controllers;


use App\Department;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use stdClass;

class DepartmentController extends Controller
{

    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Department Data
             */
            $department = Department::onlyTrashed()
                ->where('department_id', $id)
                ->first();
            if ($department) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'department' => $department,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Trashed Department found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Trashed Department Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = Department::class;

            $departments = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength,
                'trashOnly' => true
            ]);
            
            return ResponseHelper($departments);
        }
    }


    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Department Data
             */
            $department = Department::where('department_id', $id)
                ->first();
            if ($department) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'department' => $department,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Department found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Department Data
             */
            $request->validate([
                'pagelength' => 'integer',
                'page' => 'integer'
            ]);
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = Department::class;

            $departments = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength
            ]);

            return ResponseHelper($departments);
        }
    }


    function Create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:departments',
            'description' => 'string'
        ]);

        $department = new Department([
            'name' => $request->name,
            'description' => $request->description,
            'created_by_user_id' => $request->user()->user_id,
        ]);

        $department->save();
        $department->department_id = sha1('Department' . $department->id);
        $department->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Department Created Successfully";
        return ResponseHelper($response);
    }


    function Delete(Request $request, $id)
    {
        $validator = Validator::make(
            ['department_id' => $id],
            ['department_id' => 'required|exists:departments,department_id']
        );

        if ($validator) {
            $department = Department::where('department_id', $id)->first();
            if ($department) {
                $department->deleted_by_user_id = $request->user()->user_id;
                $department->active = false;
                $department->save();
                $department->delete();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Department Deleted Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Department found';
                return ResponseHelper($response);
            }
        }
    }

    function Restore(Request $request, $id)
    {
        $validator = Validator::make(
            ['department_id' => $id],
            ['department_id' => 'required|exists:departments,department_id']
        );

        if ($validator) {
            $department = Department::onlyTrashed()->where('department_id', $id)->first();
            if ($department) {
                $department->restore();
                $department->deleted_by_user_id = null;
                $department->save();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Department Restored Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Department found';
                return ResponseHelper($response);
            }
        }
    }

    function Update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:departments,department_id',
            'field' => ['required', 'string', Rule::in(Department::getUpdatableFields())],
            'value' => 'required|string'
        ]);

        $department = Department::where('department_id', $request->id)->first();

        if ($department) {
            if ($request->field === 'active') {
                if (in_array($request->value, ['active', '1', 'inactive', '0'])) {
                    if (in_array($request->value, ['active', '1'])) {
                        $request->value = 1;
                    } elseif (in_array($request->value, ['inactive', '0'])) {
                        $request->value = 0;
                    }
                } else {
                    $response = config('QuestApp.JsonResponse.Unprocessable');
                    $response['errors'] = [
                        "field" => [
                            "The active field value is invalid. It can be active/1 or inactive/0"
                        ]
                    ];
                    return ResponseHelper($response);
                }
            }
            $department->{$request->field} = $request->value;
            $department->modified_by_user_id = $request->user()->user_id;
            $department->save();

            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = 'Department has been updated';
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Department found';
            return ResponseHelper($response);
        }
    }
}
