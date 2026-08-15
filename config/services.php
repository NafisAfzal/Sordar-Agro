<?php

return [
    // Third-party service credentials would live here.
    // Payment + courier integrations in this project are SIMULATED,
    // so no live keys are required.
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],
];
