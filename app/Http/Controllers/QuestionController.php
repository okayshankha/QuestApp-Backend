<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Question;
use App\Rules\QuestionBelongsToUser;

class QuestionController extends Controller
{
    function FindTrashed(Request $request, $id = null)
    {
        if ($id) {
            /**
             * Fetch Specific Trashed Category Data
             */
            $request->merge(['question_id' => $id]);
            $request->validate([
                'question_id' => ['required', 'string', 'exists:questions,question_id', new QuestionBelongsToUser],
            ]);

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
            $request->merge(['question_id' => $id]);
            $request->validate([
                'question_id' => ['required', 'string', 'exists:questions,question_id', new QuestionBelongsToUser],
            ]);

            $question = Question::where('question_id', $id)
                ->first();
            if ($question) {
                $response = config('QuestApp.JsonResponse.success');
                $response['data']['message'] = 'Records Fetched Successfully';
                $response['data']['result'] = $question;
                return ResponseHelper($response);
            } else {
                $response = config('QuestApp.JsonResponse.no_records_found');
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
            'question' => ['required', 'string', new QuestionBelongsToUser],
            'question_image_url' => 'string',
            'options' => 'string',
            'type' => ['string', Rule::in(config('QuestApp.System.question_types'))],
            'points' => 'integer'
        ]);

        $request->type = $request->type ? strtoupper($request->type) : config('QuestApp.System.question_types')[config('QuestApp.System.default_question_types_index')];

        $_option = $request->options;
        $_accept_attributes = ['option', 'iscorrect', 'ismathexpr'];

        if ($request->type !== config('QuestApp.System.question_types')[2]  /* index 2 for TXT type */) {
            if (!JsonValidationHelper($request->options)) {
                /**
                 * Check if the given data is a valid json or not
                 */
                $response = config('QuestApp.JsonResponse.Unprocessable');
                $response['data']['errors'] = [
                    "field" => [
                        "The options field value is invalid. It has to be a valid json array"
                    ]
                ];
                return ResponseHelper($response);
            } else {
                if (!preg_match('/^[[]/', $request->options)) {
                    /**
                     * Check if the given json data is a array of json objects or not
                     */
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
                    $_option = json_decode($_option, true);

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
                            /**
                             * Each option must have a 'option' field
                             */
                            $response = config('QuestApp.JsonResponse.Unprocessable');
                            $response['data']['errors'] = [
                                "field" => [
                                    "The options field value is invalid. Each array element should have 'option' field."
                                ]
                            ];
                            return ResponseHelper($response);
                        } else {
                            if (in_array($value['option'], $_option_text)) {
                                $response = config('QuestApp.JsonResponse.Unprocessable');
                                $response['data']['errors'] = [
                                    "field" => [
                                        "The options field value is invalid. There are duplicate options."
                                    ]
                                ];
                                return ResponseHelper($response);
                            } else {
                                $_option_text[] = $value['option'];
                                if (array_key_exists('iscorrect', $value) && $value['iscorrect'] === 'true') {
                                    $has_iscorrect_count++;
                                } else {
                                    if ($value['iscorrect'] !== 'true') {
                                        unset($_option[$index]['iscorrect']);
                                    }
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

                    if ($has_iscorrect_count <= 0) {
                        $response = config('QuestApp.JsonResponse.Unprocessable');
                        $response['data']['errors'] = [
                            "field" => [
                                "The options field value is invalid. There must exist atleast one option correct."
                            ]
                        ];
                        return ResponseHelper($response);
                    }


                    if ($has_iscorrect_count <= 1) {
                        $request->type = config('QuestApp.System.question_types')[0]; // MCQ or Multiple choice questions (radio)
                    } else {
                        $request->type = config('QuestApp.System.question_types')[1]; // MTQ or Multiple true questions (checkbox)
                    }
                }
            }

            $_option = json_encode($_option, JSON_UNESCAPED_SLASHES);
        }

        $question = new Question([
            'question' => $request->question,
            'space_id' => $request->space_id,
            'question_image_url' => $request->question_image_url,
            'options' => $_option,
            'question_type' => $request->type,
            'points' => $request->points ? $request->points : 1,
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
        $request->merge(['question_id' => $id]);
        $request->validate([
            'question_id' => ['required', 'string', 'exists:questions,question_id', new QuestionBelongsToUser],
        ]);

        $validator = 1;
        if ($validator) {
            $question = Question::where('question_id', $id)->first();
            if ($question) {
                $question->deleted_by_user_id = $request->user()->user_id;
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
        $request->merge(['question_id' => $id]);
        $request->validate([
            'question_id' => ['required', 'string', 'exists:questions,question_id', new QuestionBelongsToUser],
        ]);

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

    function Update(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:questions,question_id', new QuestionBelongsToUser],
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
        }

        // else {
        //     $response = config('QuestApp.JsonResponse.404');
        //     $response['data']['message'] = 'No Question found';
        //     return ResponseHelper($response);
        // }
    }
}
