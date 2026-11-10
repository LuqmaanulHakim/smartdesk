<?php

return [
    'driver' => env('AI_DRIVER', 'fake'),
    'provider' => env('AI_PROVIDER', 'gemini'),
    'model' => env('AI_MODEL', 'gemini-3.5-flash-lite'),
    'api_key' => env('AI_API_KEY', ''),
];
