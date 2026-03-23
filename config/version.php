<?php

return [
    'current'      => env('APP_VERSION', '1.0.0'),
    'releases_api' => env(
        'RELEASES_API_URL',
        'https://api.github.com/repos/YOUR_ORG/church-platform/releases/latest'
    ),
];
