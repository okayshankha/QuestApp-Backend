<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Subject;
use App\Examination;
use App\ExamQuestionMap;

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

    function MapQuestions(Request $request, $action = 'connect')
    {
        $question_ids = null;
        if ($request->question_ids) {
            $question_ids = $question_ids;
            $request->merge([
                'question_id' => array_filter(array_unique(explode(',', trim($request->question_ids, ","))))
            ]);
        }

        $request->validate([
            "question_ids" => "required|string",
            "question_id" => "array",
            'question_id.*' => 'required|exists:questions,question_id',
            'examination_id' => 'required|exists:examinations,examination_id'
        ]);

        $index = 0;
        foreach ($request->question_id as $question_id) {
            $map = ExamQuestionMap::withTrashed()
                ->where('question_id', $question_id)
                ->where('examination_id', $request->examination_id)->first();


            if ($action == 'connect') {
                if (!$map) {
                    $map = new ExamQuestionMap;
                    $map->question_id = $request->question_id[$index++];
                    $map->examination_id = $request->examination_id;
                    $map->created_by_user_id = $request->user()->user_id;

                    // dd($map);
                    $map->save();
                    $map->exam_question_map_id = sha1('ExamQuestionMap' . $map->id);
                    $map->save();
                } else {
                    if ($map->deleted_by_user_id) {
                        $map->restore();
                    } else {
                        // Mapping already exists.
                    }
                }
            } else if ($action == 'disconnect') {
                if ($map) {
                    if (!$map->deleted_by_user_id) {
                        $map->delete();
                    } else {
                        // Mapping already deleted.
                    }
                }
            }
        }

        $response = config('QuestApp.JsonResponse.success');
        $response['data']['message'] = 'Questions has been added';
        return ResponseHelper($response);
    }

    function GetMappedQuestions(Request $request, $id)
    {
        $validator = Validator::make(
            ['examination_id' => $id],
            ['examination_id' => 'required|exists:examinations,examination_id']
        );

        if ($validator) {
            // selectRaw('question_id as question')->
            $map = ExamQuestionMap::where('examination_id', $id)->get();

            if ($map->count() > 0) {
                foreach($map as & $val){
                    $val['question'] = $val['question_id'];
                    unset($val['question_id']);
                }
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = [
                    'records' => $map
                ];
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Records found';
            }
            return ResponseHelper($response);
        }
    }
}
