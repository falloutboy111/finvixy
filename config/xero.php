<?php

return [
    'client_id' => env('XERO_CLIENT_ID'),
    'client_secret' => env('XERO_CLIENT_SECRET'),
    'redirect_uri' => env('XERO_REDIRECT_URI'),
    'scopes' => env('XERO_SCOPES', 'openid profile email accounting.transactions accounting.contacts offline_access'),

    'authorize_url' => 'https://login.xero.com/identity/connect/authorize',
    'token_url' => 'https://identity.xero.com/connect/token',
    'connections_url' => 'https://api.xero.com/connections',
    'api_base' => 'https://api.xero.com/api.xro/2.0/',
];
