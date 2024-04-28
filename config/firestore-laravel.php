<?php

return [
    'project_id'  => env('GOOGLE_CLOUD_PROJECT'),
    'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'database'    => env('FIRESTORE_DATABASE', '(default)'),

    'cache' => [
        'collection'           => env('FIRESTORE_CACHE_COLLECTION', 'cache'),
        'key_attribute'        => env('FIRESTORE_CACHE_KEY_ATTR', 'key'),
        'value_attribute'      => env('FIRESTORE_CACHE_VALUE_ATTR', 'value'),
        'expiration_attribute' => env('FIRESTORE_CACHE_EXPIRATION_ATTR', 'expired_at'),
    ],

    'session' => [
        'collection' => env('FIRESTORE_SESSION_COLLECTION', 'sessions'),
    ],
];
