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
    'error' => [
        'httpStatusCode' => 200, // Okay
        'data' => [
            'message' => 'Error occured'
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
];
