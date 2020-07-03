<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use  App\Subject;
use App\Examination;

class ExaminationController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Category Data
             */
            $examinations = Examination::onlyTrashed()
                ->where('examination_id', $id)
                ->first();
            if ($examinations) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $examinations,
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

            $Model = Examination::class;

            $examinations = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength,
                'trashOnly' => true
            ]);

            return ResponseHelper($examinations);
        }
    }

    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Category Data
             */
            $examinations = Examination::where('examination_id', $id)
                ->first();
            if ($examinations) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'record' => $examinations,
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

            $Model = Examination::class;

            $examinations = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength
            ]);

            return ResponseHelper($examinations);
        }
    }


    function Create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:examinations',
            'description' => 'string',
            'subject_id' => 'required|string|exists:subjects,subject_id'
        ]);

        $examination = new Examination([
            'name' => $request->name,
            'subject_id' => $request->subject_id,
            'description' => $request->description,
            'created_by_user_id' => $request->user()->user_id,
        ]);

        $examination->save();
        $examination->examination_id = sha1('Examination' . $examination->id);
        $examination->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Examination Created Successfully";
        return ResponseHelper($response);
    }

    function Delete(Request $request, $id)
    {
        $validator = Validator::make(
            ['examination_id' => $id],
            ['examination_id' => 'required|exists:examinations,examination_id']
        );

        if ($validator) {
            $examination = Examination::where('examination_id', $id)->first();
            if ($examination) {
                $examination->deleted_by_user_id = $request->user()->user_id;
                $examination->active = false;
                $examination->save();
                $examination->delete();

                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Examination Deleted Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Examination found';
                return ResponseHelper($response);
            }
        }
    }

    function Restore(Request $request, $id)
    {
        $validator = Validator::make(
            ['examination_id' => $id],
            ['examination_id' => 'required|exists:examinations,examination_id']
        );

        if ($validator) {
            $examination = Examination::onlyTrashed()->where('examination_id', $id)->first();
            if ($examination) {
                $examination->restore();
                $examination->deleted_by_user_id = null;
                $examination->save();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Examination Restored Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Examination found';
                return ResponseHelper($response);
            }
        }
    }


    function Update(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:examinations,examination_id',
            'field' => ['required', 'string', Rule::in(Examination::getUpdatableFields())],
            'value' => 'required|string'
        ]);

        $examination = Examination::where('examination_id', $request->id)->first();

        if ($examination) {
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
            if ($request->field === 'subject_id') {
                $subject = Subject::where('subject_id', $request->value)->first();
                if (!$subject) {
                    $response = config('QuestApp.JsonResponse.Unprocessable');
                    $response['data']['errors'] = [
                        "subject_id" => [
                            "The selected field is invalid."
                        ]
                    ];
                    return ResponseHelper($response);
                }
            }

            $examination->{$request->field} = $request->value;
            $examination->modified_by_user_id = $request->user()->user_id;
            $examination->save();

            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = 'Examination has been updated';
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Examination found';
            return ResponseHelper($response);
        }
    }
}
