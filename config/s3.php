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

    // backup.agent settings
    'backup' => [
        // Minutes past a job's expected run time before it's considered missed —
        // see BackupJobRunsService::getMissedJobs().
        'missed_grace_minutes' => env('S3_BACKUP_MISSED_GRACE_MINUTES', 30),
    ],

    // Server-package subscriptions and overage billing — see AccountsService::subscribeToServerPackage()
    'packaging' => [
        // Safety-net multiplier applied to the sum of included storage/egress
        // across an account's active packages when raising quota_storage_bytes /
        // quota_egress_bytes_mo. These remain hard abuse ceilings (QuotaHelper /
        // CheckQuotasJob) — normal overage bills instead of blocking.
        'storage_quota_safety_multiplier'  => env('S3_PACKAGING_STORAGE_QUOTA_SAFETY_MULTIPLIER', 3),
        'egress_quota_safety_multiplier'   => env('S3_PACKAGING_EGRESS_QUOTA_SAFETY_MULTIPLIER', 3),
        // Egress allowance unlocked the first time an account subscribes to
        // any server package (egress stays account-wide and blended, not
        // per-server — see S3EgressOverageBilling).
        'default_included_egress_bytes_mo' => env('S3_PACKAGING_DEFAULT_INCLUDED_EGRESS_BYTES_MO', 0),
        'egress_overage_price_per_gb'       => env('S3_PACKAGING_EGRESS_OVERAGE_PRICE_PER_GB', 0.08),
        // marketplace_products.marketplace_market_id is required (a sales
        // channel: domain/currency/language/country) — set this to the uuid
        // of the market S3 storage should be sold through.
        'marketplace_market_id' => env('S3_PACKAGING_MARKETPLACE_MARKET_ID'),

        // Default paid tiers seeded on every server by
        // ServersService::ensurePackaging() (new servers via create(), and
        // existing servers via `php artisan s3:ensure-server-packaging`),
        // in addition to the always-present PAYG package.
        //
        // *** PLACEHOLDER ECONOMICS — needs business sign-off before use
        // against real servers. *** Both price and overage rate are
        // computed relative to that server's own price_per_gb (so pricing
        // scales correctly across servers with different hardware costs,
        // same as the auto-PAYG package already does), not as fixed
        // absolute dollar amounts:
        //   price (flat monthly fee) = included_storage_bytes/1GB * server.price_per_gb * flat_fee_multiplier
        //   overage_price_per_gb_storage = server.price_per_gb * overage_discount_percent / 100
        // A flat_fee_multiplier below 1.0 means the included allowance is
        // cheaper per-GB than paying PAYG for the same amount (the
        // incentive to commit to a tier); overage_discount_percent below
        // 100 means overage past the included amount is also discounted
        // vs raw PAYG.
        'default_tiers' => [
            [
                'name' => '1TB Package',
                'sku'  => '1tb',
                'included_storage_bytes'   => 1099511627776, // 1 TiB
                'flat_fee_multiplier'      => 0.8,
                'overage_discount_percent' => 100,
            ],
            [
                'name' => '5TB Package',
                'sku'  => '5tb',
                'included_storage_bytes'   => 5497558138880, // 5 TiB
                'flat_fee_multiplier'      => 0.7,
                'overage_discount_percent' => 90,
            ],
            [
                'name' => '20TB Package',
                'sku'  => '20tb',
                'included_storage_bytes'   => 21990232555520, // 20 TiB
                'flat_fee_multiplier'      => 0.6,
                'overage_discount_percent' => 80,
            ],
        ],
    ],
];