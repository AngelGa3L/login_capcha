<?php

return [
    'secret' => env('RECAPTCHAV3_SECRET'),
    'sitekey' => env('RECAPTCHAV3_SITEKEY'),
    'options' => [
        'timeout' => 30,
    ],
];
