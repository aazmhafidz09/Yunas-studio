<?php

return [

    'secret_key' => env('XENDIT_SECRET_KEY'),

    'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),

    'callback_token' => env('XENDIT_CALLBACK_TOKEN'),

    'is_production' => env('XENDIT_IS_PRODUCTION', false),

];