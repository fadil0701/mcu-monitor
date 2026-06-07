<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin (bootstrap via artisan user:create-admin --from-env)
    |--------------------------------------------------------------------------
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME'),
        'email' => env('SUPER_ADMIN_EMAIL'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

];
