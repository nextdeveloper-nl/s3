<?php
return [
    'scopes'    =>  [
        'global' => [
            //  Dont do this here because it makes infinite loop with user object.
            '\NextDeveloper\IAM\Database\Scopes\AuthorizationScope',
            '\NextDeveloper\Commons\Database\GlobalScopes\LimitScope',
        ]
    ],

    // AES-256-GCM key for encrypting IAM secret keys at rest.
    // Must be a 32-byte raw string or a 64-character hex string.
    // Set via S3_ENCRYPTION_KEY environment variable.
    'encryption_key' => env('S3_ENCRYPTION_KEY'),

    // Default quota values applied when creating a new account
    'defaults' => [
        'quota_storage_bytes'   => env('S3_DEFAULT_QUOTA_STORAGE_BYTES', 10737418240),   // 10 GB
        'quota_egress_bytes_mo' => env('S3_DEFAULT_QUOTA_EGRESS_BYTES_MO', 107374182400), // 100 GB
        'quota_max_buckets'     => env('S3_DEFAULT_QUOTA_MAX_BUCKETS', 10),
        'quota_max_objects'     => env('S3_DEFAULT_QUOTA_MAX_OBJECTS', 1000000),
    ],

    // WORM commitment settings
    'worm' => [
        'price_per_gb_mo'  => env('S3_WORM_PRICE_PER_GB_MO', 0.023),
        'purge_grace_days' => env('S3_WORM_PURGE_GRACE_DAYS', 30),
    ],
];