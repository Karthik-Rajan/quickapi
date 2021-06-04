<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
     */
    'supportsCredentials' => false,
    'allowedOrigins'      => ['*'],
    'allowedHeaders'      => ['Content-Type', 'Authorization', 'X-Requested-With', 'quest', 'Cookie', 'Accept', 'Accept-Encoding', 'Connection', 'Host', 'Origin', 'Referer', 'sec-ch-ua', 'sec-ch-ua-mobile', 'Sec-Fetch-Dest', 'Sec-Fetch-Mode', 'Sec-Fetch-Site', 'User-Agent', 'Access-Control-Allow-Origin'],
    'allowedMethods'      => ['*'],
    'exposedHeaders'      => [],
    'maxAge'              => 0,
];
