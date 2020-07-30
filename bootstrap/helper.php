<?php


function GetCustomClassIdString($Model)
{
    $modelClass = explode('\\', get_class(new $Model))[1];
    $modelClassIdString = '_id';

    switch ($modelClass) {
        case 'ExamQuestionMap':
            $modelClassIdString = 'exam_question_map' . $modelClassIdString;
            break;
        case 'Question':
            $modelClassIdString = 'question' . $modelClassIdString;
            break;
        case 'Subject':
            $modelClassIdString = 'subject' . $modelClassIdString;
            break;
        case 'User':
            $modelClassIdString = 'user' . $modelClassIdString;
            break;
        case 'Examination':
            $modelClassIdString = 'examination' . $modelClassIdString;
            break;
        case 'EntityUserMapping':
            $modelClassIdString = 'entity_user_mapping' . $modelClassIdString;
            break;
        case 'AssessmentRecord':
            $modelClassIdString = 'class' . $modelClassIdString;
            break;
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


function ResponseHelper($parameter)
{
    try {
        if (gettype($parameter['httpStatusCode']) === 'integer') {
            return response()->json($parameter['data'])->setStatusCode($parameter['httpStatusCode'])
                ->header('Content-Type', 'application/json');
        } else {
            throw new Exception("invalid_response_array");
        }
    } catch (Exception $e) {
        $parameter = config('jsonresponse.invalid_response_array');
        return response()->json($parameter['data'])->setStatusCode($parameter['httpStatusCode'])
            ->header('Content-Type', 'application/json');
    }
}

function JsonValidationHelper($data = NULL)
{
    if (!empty($data)) {
        @json_decode($data);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
}
