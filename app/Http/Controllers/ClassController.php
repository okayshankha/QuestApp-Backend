<?php

namespace App\Http\Controllers;


use App\MyClass;
use App\Rules\ClassBelongsToUser;
use App\Rules\SpaceBelongsToUser;
use App\Space;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Class Data
             */
            $request->merge(['class_id' => $id]);
            $request->validate([
                'class_id' => ['required', 'string', 'exists:my_classes,class_id', new ClassBelongsToUser],
            ]);

            $class = MyClass::onlyTrashed()
                ->where('class_id', $id)
                ->first();
            if ($class) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $class,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Trashed Record found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Trashed Class Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = MyClass::class;

            $classes = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength,
                'trashOnly' => true
            ]);

            return ResponseHelper($classes);
        }
    }

    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Class Data
             */
            $request->merge(['class_id' => $id]);
            $request->validate([
                'class_id' => ['required', 'string', 'exists:my_classes,class_id', new ClassBelongsToUser],
            ]);

            $class = MyClass::where('class_id', $id)
                ->first();
            if ($class) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $class,
                ];
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Record found';
                return ResponseHelper($response);
            }
        } else {
            /**
             * Fetch All Class Data
             */
            $pagelength = $request->query('pagelength');
            $page = $request->query('page');

            $Model = MyClass::class;

            $classes = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength
            ]);

            return ResponseHelper($classes);
        }
    }


    function Create(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', new QuestionBelongsToUser],
            'description' => 'string',
            'space_id' => ['required', 'string', 'exists:spaces,space_id', new SpaceBelongsToUser]
        ]);


        $class = new MyClass([
            'name' => $request->name,
            'space_id' => $request->space_id,
            'description' => $request->description,
            'created_by_user_id' => $request->user()->user_id,
        ]);

        $class->save();
        $class->class_id = sha1('Class' . $class->id);
        $class->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Class Created Successfully";
        return ResponseHelper($response);
    }

    function Delete(Request $request, $id)
    {
        $request->merge(['class_id' => $id]);
        $request->validate([
            'class_id' => ['required', 'string', 'exists:my_classes,class_id', new ClassBelongsToUser],
        ]);


        $class = MyClass::where('class_id', $id)->first();
        if ($class) {
            $class->deleted_by_user_id = $request->user()->user_id;
            $class->active = false;
            $class->save();
            $class->delete();
            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = "Class Deleted Successfully";
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Class found';
            return ResponseHelper($response);
        }
    }

    function Restore(Request $request, $id)
    {
        $request->merge(['class_id' => $id]);
        $request->validate([
            'class_id' => ['required', 'string', 'exists:my_classes,class_id', new ClassBelongsToUser],
        ]);

        $class = MyClass::onlyTrashed()->where('class_id', $id)->first();
        if ($class) {
            $class->restore();
            $class->deleted_by_user_id = null;
            $class->save();
            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = "Class Restored Successfully";
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Class found';
            return ResponseHelper($response);
        }
    }


    function Update(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:my_classes,class_id', new ClassBelongsToUser],
            'field' => ['required', 'string', Rule::in(MyClass::getUpdatableFields())],
            'value' => 'required|string'
        ]);

        $class = MyClass::where('class_id', $request->id)->first();

        if ($class) {
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
            if ($request->field === 'space_id') {
                $space = Space::where('space_id', $request->value)->first();
                if (!$space) {
                    $response = config('QuestApp.JsonResponse.Unprocessable');
                    $response['data']['errors'] = [
                        "space_id" => [
                            "The selected field is invalid."
                        ]
                    ];
                    return ResponseHelper($response);
                }
            }

            $class->{$request->field} = $request->value;
            $class->modified_by_user_id = $request->user()->user_id;
            $class->save();

            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = 'Class has been updated';
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Class found';
            return ResponseHelper($response);
        }
    }

    function InviteStudent(Request $request)
    {
        $request->validate([
            'id' => ['required_without:email', 'exists:users,user_id', new VerifyStudent],
            'email' => ['required_without:id', 'exists:users,email', new VerifyStudent],
            'class_id' => ['required', 'exists:classes,class_id', new ClassBelongsToUser],
        ]);

        $student = null;

        if ($request->id) {
            $student = User::where('user_id', $request->id)->first();
        } else if ($request->email) {
            $student = User::where('email', $request->email)->first();
        }

        $payload['type'] = 'space';
        $payload['data'] = Space::where('space_id', $request->space_id)->first();

        $student->notify(new InvitationToStudent($student, $payload));

        return 'yeeee';
    }
}

