<?php
return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'resend' => ['key' => env('RESEND_KEY')],
    'ses' => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'gemini' => ['key' => env('GEMINI_API_KEY'), 'model' => env('GEMINI_MODEL', 'gemini-2.5-flash')],
    'stripe' => ['secret' => env('STRIPE_SECRET_KEY'), 'publishable' => env('STRIPE_PUBLISHABLE_KEY'), 'webhook_secret' => env('STRIPE_WEBHOOK_SECRET')],
];
