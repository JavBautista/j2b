<?php

return [
    'base_url'   => env('HUB_CFDI_BASE_URL', 'https://dev.techbythree.com/api'),
    'api_token'  => env('HUB_CFDI_API_TOKEN'),
    'client_id'  => env('HUB_CFDI_CLIENT_ID'),
    'is_sandbox' => env('HUB_CFDI_SANDBOX', true),
    'timeout'    => env('HUB_CFDI_TIMEOUT', 30),
    'retry'      => ['times' => 2, 'sleep' => 1000],
];
