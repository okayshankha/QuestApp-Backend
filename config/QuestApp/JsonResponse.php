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

    'Unauthenticated' => [
        'httpStatusCode' => 401, // Unauthenticated
        'data' => [
            'message' => 'Unauthenticated'
        ]
    ],
];
