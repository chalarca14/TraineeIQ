<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración para compartir recursos entre orígenes (CORS)
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar los parámetros para compartir recursos entre
    | orígenes (CORS), que determinan qué operaciones pueden ejecutar los
    | navegadores. Ajusta estos valores según el entorno de la aplicación.
    |
    | Más información: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
