<?php

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
