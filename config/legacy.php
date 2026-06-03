<?php

return [
    'encryption_key' => env('LEGACY_ENCRYPTION_KEY', env('ENCRYPTION_KEY', '')),
    'allow_admin_init' => env('ALLOW_ADMIN_INIT', '0') === '1',
];
