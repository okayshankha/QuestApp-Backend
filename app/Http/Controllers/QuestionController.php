<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Question;

class QuestionController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Category Data
             */
            $question = Question::onlyTrashed()
                ->where('question_id', $id)
                ->first();
            if ($question) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $question;
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

            $Model = Question::class;

            $questions = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength,
                'trashOnly' => true
            ]);

            return ResponseHelper($questions);
        }
    }

    function Find(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Category Data
             */
            $question = Question::where('question_id', $id)
                ->first();
            if ($question) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $question;
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

            $Model = Question::class;

            $questions = $this->FetchPagedRecords($Model, [
                'page' => $page,
                'pagelength' => $pagelength
            ]);

            return ResponseHelper($questions);
        }
    }

    function Create(Request $request)
    {
        $request->validate([
            'question' => 'required|string|unique:questions',
            'question_image_url' => 'string',
            'options' => 'string',
        ]);

        $_option = null;
        $_accept_attributes = ['option', 'iscorrect', 'ismathexpr'];

        if (!JsonValidationHelper($request->options)) {
            $response = config('QuestApp.JsonResponse.Unprocessable');
            $response['data']['errors'] = [
                "field" => [
                    "The options field value is invalid. It has to be a valid json array"
                ]
            ];
            return ResponseHelper($response);
        } else {
            if (!preg_match('/^[[]/', $request->options)) {
                $response = config('QuestApp.JsonResponse.Unprocessable');
                $response['data']['errors'] = [
                    "field" => [
                        "The options field value is invalid. It has to be a valid json array"
                    ]
                ];
                return ResponseHelper($response);
            } else {
                $has_iscorrect_count = 0;
                $index = -1;
                $_option_text = [];
                $_option = json_decode($request->options, true);

                if (count($_option) < 2) {
                    $response = config('QuestApp.JsonResponse.Unprocessable');
                    $response['data']['errors'] = [
                        "field" => [
                            "The options field value is invalid. There should be atleast 2 options for each questions."
                        ]
                    ];
                    return ResponseHelper($response);
                }

                foreach ($_option as $value) {
                    $index++;
                    if (!array_key_exists('option', $value)) {
                        $response = config('QuestApp.JsonResponse.Unprocessable');
                        $response['data']['errors'] = [
                            "field" => [
                                "The options field value is invalid. Each option should need to have 'option'."
                            ]
                        ];
                        return ResponseHelper($response);
                    } else {
                        if (in_array($value, $_option_text)) {
                            $response = config('QuestApp.JsonResponse.Unprocessable');
                            $response['data']['errors'] = [
                                "field" => [
                                    "The options field value is invalid. There are duplicate options."
                                ]
                            ];
                            return ResponseHelper($response);
                        } else {
                            $_option_text[] = $value;
                            if (array_key_exists('iscorrect', $value) && $value['iscorrect'] === 'true') {
                                $has_iscorrect_count++;
                            } else {
                                if ($value['iscorrect'] !== 'true') {
                                    unset($_option[$index]['iscorrect']);
                                }

                                $keys = array_keys($value);
                                foreach ($keys as $key_value) {
                                    if (!in_array($key_value, $_accept_attributes)) {
                                        unset($_option[$index][$key_value]);
                                    }
                                }
                            }
                        }
                    }
                }

                if ($has_iscorrect_count !== 1) {
                    $response = config('QuestApp.JsonResponse.Unprocessable');
                    $response['data']['errors'] = [
                        "field" => [
                            "The options field value is invalid. There must exist one option correct."
                        ]
                    ];
                    return ResponseHelper($response);
                }
            }
        }

        $question = new Question([
            'question' => $request->question,
            'question_image_url' => $request->question_image_url,
            'options' => json_encode($_option, JSON_UNESCAPED_SLASHES),
            'created_by_user_id' => $request->user()->user_id,
        ]);

        $question->save();
        $question->question_id = sha1('Question' . $question->id);
        $question->save();

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = "Question Created Successfully";
        return ResponseHelper($response);
    }

    function Delete(Request $request, $id)
    {
        $validator = Validator::make(
            ['question_id' => $id],
            ['question_id' => 'required|exists:questions,question_id']
        );

        if ($validator) {
            $question = Question::where('question_id', $id)->first();
            if ($question) {
                $question->deleted_by_user_id = $request->user()->user_id;
                // $question->active = false;
                $question->save();
                $question->delete();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Question Deleted Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Question found';
                return ResponseHelper($response);
            }
        }
    }

    function Restore(Request $request, $id)
    {
        $validator = Validator::make(
            ['question_id' => $id],
            ['question_id' => 'required|exists:questions,question_id']
        );

        if ($validator) {
            $question = Question::onlyTrashed()->where('question_id', $id)->first();
            if ($question) {
                $question->restore();
                $question->deleted_by_user_id = null;
                $question->save();
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = "Question Restored Successfully";
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.404');
                $response['data']['message'] = 'No Question found';
                return ResponseHelper($response);
            }
        }
    }

    function Update(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:questions,question_id',
            'field' => ['required', 'string', Rule::in(Question::getUpdatableFields())],
            'value' => 'required|string'
        ]);

        $question = Question::where('question_id', $request->id)->first();

        if ($question) {
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

            $question->{$request->field} = $request->value;
            $question->modified_by_user_id = $request->user()->user_id;
            $question->save();

            $response = config('QuestApp.JsonResponse.success');
            $response['data']['message'] = 'Question has been updated';
            return ResponseHelper($response);
        } else {
            $response = config('QuestApp.JsonResponse.404');
            $response['data']['message'] = 'No Question found';
            return ResponseHelper($response);
        }
    }
}
