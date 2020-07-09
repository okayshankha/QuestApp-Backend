<?php

namespace App\Http\Controllers;

use App\Category;
use  App\Subject;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Category Data
             */
            $subjects = Subject::onlyTrashed()
                ->where('subject_id', $id)
                ->first();
            if ($subjects) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $subjects,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Trashed Records found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Trashed Category Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = Subject::class;

            $subjects = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength,
                'trashOnly' => true
            ]);

            return ResponseHelper($subjects);
        }
    }

    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Category Data
             */
            $subjects = Subject::where('subject_id', $id)
                ->first();
            if ($subjects) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $subjects,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Records found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Subjects Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = Subject::class;

            $subjects = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength
            ]);

            return ResponseHelper($subjects);
        }
    }


    function Create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:subjects',
            'description' => 'string',
            'category_id' => 'required|string|exists:categories,category_id'
        ]);

        $subject = new Subject([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'created_by_user_id' => $request->user()->user_id,
        ]);

        $subject->save();
        $subject->subject_id = sha1('Subject' . $subject->id);
        $subject->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Subject Created Successfully";
        return ResponseHelper($response);
    }

    function Delete(Request $request, $id)
    {
        $validator = Validator::make(
            ['subject_id' => $id],
            ['subject_id' => 'required|exists:subjects,subject_id']
        )->validate();

        if ($validator) {
            $subject = Subject::where('subject_id', $id)->first();
            if ($subject) {
                $subject->deleted_by_user_id = $request->user()->user_id;
                $subject->active = false;
                $subject->save();
                $subject->delete();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Subject Deleted Successfully";
                return ResponseHelper($response);
            }
        }
    }

    function Restore(Request $request, $id)
    {
        $validator = Validator::make(
            ['subject_id' => $id],
            ['subject_id' => 'required|exists:subjects,subject_id']
        );

        if ($validator) {
            $subject = Subject::onlyTrashed()->where('subject_id', $id)->first();
            if ($subject) {
                $subject->restore();
                $subject->deleted_by_user_id = null;
                $subject->save();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Subject Restored Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Subject found';
                return ResponseHelper($response);
            }
        }
    }


    function Update(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:subjects,subject_id',
            'field' => ['required', 'string', Rule::in(Subject::getUpdatableFields())],
            'value' => 'required|string'
        ]);

        $subject = Subject::where('subject_id', $request->id)->first();

        if ($subject) {
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
            if ($request->field === 'category_id') {
                $category = Category::where('category_id', $request->value)->first();
                if (!$category) {
                    $response = config('QuestApp.JsonResponse.Unprocessable');
                    $response['data']['errors'] = [
                        "category_id" => [
                            "The selected field is invalid."
                        ]
                    ];
                    return ResponseHelper($response);
                }
            }

            $subject->{$request->field} = $request->value;
            $subject->modified_by_user_id = $request->user()->user_id;
            $subject->save();

            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = 'Subject has been updated';
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Subject found';
            return ResponseHelper($response);
        }
    }
}
