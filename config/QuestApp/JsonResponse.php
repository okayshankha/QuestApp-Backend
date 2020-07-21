<?php

return [
    'success' => [
        'httpStatusCode' => 200, // Okay
        'data' => [
            'message' => 'Success'
        ]
    ],
    'created' => [
        'httpStatusCode' => 201, // created
        'data' => [
            'message' => 'Created'
        ]
    ],
    'no_records_found' => [
        'httpStatusCode' => 200, // Okay
        'data' => [
            'message' => 'No Records Found'
        ]
    ],
    'error' => [
        'httpStatusCode' => 200, // Okay
        'data' => [
            'message' => 'Error occured'
        ]
    ],
    '400' => [
        'httpStatusCode' => 400, // Bad Request
        'data' => [
            'message' => 'Bad Request'
        ]
    ],
    '404' => [
        'httpStatusCode' => 404, // Not Found
        'data' => [
            'message' => 'Not Found'
        ]
    ],
    '403' => [
        'httpStatusCode' => 403, // Forbidden
        'data' => [
            'message' => 'Forbidden'
        ]
    ],
    'Unauthenticated' => [
        'httpStatusCode' => 401, // Unauthenticated
        'data' => [
            'message' => 'Unauthenticated'
        ]
    ],
    'Unprocessable' => [
        'httpStatusCode' => 422, // Unprocessable
        'data' => [
            "message" => "The given data was invalid.",
            "errors" => []
            // [
            //     "name" => [
            //         "The name field is required."
            //     ]
            // ]
        ]
    ]
];
