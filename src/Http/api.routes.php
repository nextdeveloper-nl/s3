<?php

Route::prefix('s3')->group(function () {
Route::prefix('servers')->group(
    function () {
        Route::get('/', 'Servers\ServersController@index');
        Route::get('/actions', 'Servers\ServersController@getActions');

        Route::get('{s3_servers}/tags ', 'Servers\ServersController@tags');
        Route::post('{s3_servers}/tags ', 'Servers\ServersController@saveTags');
        Route::get('{s3_servers}/addresses ', 'Servers\ServersController@addresses');
        Route::post('{s3_servers}/addresses ', 'Servers\ServersController@saveAddresses');

        Route::get('/{s3_servers}/{subObjects}', 'Servers\ServersController@relatedObjects');
        Route::get('/{s3_servers}', 'Servers\ServersController@show');

        Route::post('/', 'Servers\ServersController@store');
        Route::post('/{s3_servers}/do/{action}', 'Servers\ServersController@doAction');

        Route::patch('/{s3_servers}', 'Servers\ServersController@update');
        Route::delete('/{s3_servers}', 'Servers\ServersController@destroy');

        // storaged agent endpoints
        Route::post('/{s3_servers}/telemetry', 'Servers\ServersController@telemetry');
        Route::get('/{s3_servers}/events', 'Servers\ServersController@events');
        Route::post('/{s3_servers}/ack', 'Servers\ServersController@ack');
        Route::patch('/{s3_servers}/state', 'Servers\ServersController@state');
    }
);

Route::prefix('accounts')->group(
    function () {
        Route::get('/', 'Accounts\AccountsController@index');
        Route::get('/actions', 'Accounts\AccountsController@getActions');

        Route::get('{s3_accounts}/tags ', 'Accounts\AccountsController@tags');
        Route::post('{s3_accounts}/tags ', 'Accounts\AccountsController@saveTags');
        Route::get('{s3_accounts}/addresses ', 'Accounts\AccountsController@addresses');
        Route::post('{s3_accounts}/addresses ', 'Accounts\AccountsController@saveAddresses');

        Route::get('/{s3_accounts}/{subObjects}', 'Accounts\AccountsController@relatedObjects');
        Route::get('/{s3_accounts}', 'Accounts\AccountsController@show');

        Route::post('/', 'Accounts\AccountsController@store');
        Route::post('/{s3_accounts}/do/{action}', 'Accounts\AccountsController@doAction');

        Route::patch('/{s3_accounts}', 'Accounts\AccountsController@update');
        Route::delete('/{s3_accounts}', 'Accounts\AccountsController@destroy');
    }
);

Route::prefix('access-keys')->group(
    function () {
        Route::get('/', 'AccessKeys\AccessKeysController@index');
        Route::get('/actions', 'AccessKeys\AccessKeysController@getActions');

        Route::get('{s3_access_keys}/tags ', 'AccessKeys\AccessKeysController@tags');
        Route::post('{s3_access_keys}/tags ', 'AccessKeys\AccessKeysController@saveTags');
        Route::get('{s3_access_keys}/addresses ', 'AccessKeys\AccessKeysController@addresses');
        Route::post('{s3_access_keys}/addresses ', 'AccessKeys\AccessKeysController@saveAddresses');

        Route::get('/{s3_access_keys}/{subObjects}', 'AccessKeys\AccessKeysController@relatedObjects');
        Route::get('/{s3_access_keys}', 'AccessKeys\AccessKeysController@show');

        Route::post('/', 'AccessKeys\AccessKeysController@store');
        Route::post('/{s3_access_keys}/do/{action}', 'AccessKeys\AccessKeysController@doAction');

        Route::patch('/{s3_access_keys}', 'AccessKeys\AccessKeysController@update');
        Route::delete('/{s3_access_keys}', 'AccessKeys\AccessKeysController@destroy');
    }
);

Route::prefix('buckets')->group(
    function () {
        Route::get('/', 'Buckets\BucketsController@index');
        Route::get('/actions', 'Buckets\BucketsController@getActions');

        Route::get('{s3_buckets}/tags ', 'Buckets\BucketsController@tags');
        Route::post('{s3_buckets}/tags ', 'Buckets\BucketsController@saveTags');
        Route::get('{s3_buckets}/addresses ', 'Buckets\BucketsController@addresses');
        Route::post('{s3_buckets}/addresses ', 'Buckets\BucketsController@saveAddresses');

        Route::get('/{s3_buckets}/{subObjects}', 'Buckets\BucketsController@relatedObjects');
        Route::get('/{s3_buckets}', 'Buckets\BucketsController@show');

        Route::post('/', 'Buckets\BucketsController@store');
        Route::post('/{s3_buckets}/do/{action}', 'Buckets\BucketsController@doAction');

        Route::patch('/{s3_buckets}', 'Buckets\BucketsController@update');
        Route::delete('/{s3_buckets}', 'Buckets\BucketsController@destroy');
    }
);

Route::prefix('server-telemetry')->group(
    function () {
        Route::get('/', 'ServerTelemetry\ServerTelemetryController@index');
        Route::get('/actions', 'ServerTelemetry\ServerTelemetryController@getActions');

        Route::get('{s3_server_telemetry}/tags ', 'ServerTelemetry\ServerTelemetryController@tags');
        Route::post('{s3_server_telemetry}/tags ', 'ServerTelemetry\ServerTelemetryController@saveTags');
        Route::get('{s3_server_telemetry}/addresses ', 'ServerTelemetry\ServerTelemetryController@addresses');
        Route::post('{s3_server_telemetry}/addresses ', 'ServerTelemetry\ServerTelemetryController@saveAddresses');

        Route::get('/{s3_server_telemetry}/{subObjects}', 'ServerTelemetry\ServerTelemetryController@relatedObjects');
        Route::get('/{s3_server_telemetry}', 'ServerTelemetry\ServerTelemetryController@show');

        Route::post('/', 'ServerTelemetry\ServerTelemetryController@store');
        Route::post('/{s3_server_telemetry}/do/{action}', 'ServerTelemetry\ServerTelemetryController@doAction');

        Route::patch('/{s3_server_telemetry}', 'ServerTelemetry\ServerTelemetryController@update');
        Route::delete('/{s3_server_telemetry}', 'ServerTelemetry\ServerTelemetryController@destroy');
    }
);

Route::prefix('usage-snapshots')->group(
    function () {
        Route::get('/', 'UsageSnapshots\UsageSnapshotsController@index');
        Route::get('/actions', 'UsageSnapshots\UsageSnapshotsController@getActions');

        Route::get('{s3_usage_snapshots}/tags ', 'UsageSnapshots\UsageSnapshotsController@tags');
        Route::post('{s3_usage_snapshots}/tags ', 'UsageSnapshots\UsageSnapshotsController@saveTags');
        Route::get('{s3_usage_snapshots}/addresses ', 'UsageSnapshots\UsageSnapshotsController@addresses');
        Route::post('{s3_usage_snapshots}/addresses ', 'UsageSnapshots\UsageSnapshotsController@saveAddresses');

        Route::get('/{s3_usage_snapshots}/{subObjects}', 'UsageSnapshots\UsageSnapshotsController@relatedObjects');
        Route::get('/{s3_usage_snapshots}', 'UsageSnapshots\UsageSnapshotsController@show');

        Route::post('/', 'UsageSnapshots\UsageSnapshotsController@store');
        Route::post('/{s3_usage_snapshots}/do/{action}', 'UsageSnapshots\UsageSnapshotsController@doAction');

        Route::patch('/{s3_usage_snapshots}', 'UsageSnapshots\UsageSnapshotsController@update');
        Route::delete('/{s3_usage_snapshots}', 'UsageSnapshots\UsageSnapshotsController@destroy');
    }
);

Route::prefix('bandwidth-monthlies')->group(
    function () {
        Route::get('/', 'BandwidthMonthlies\BandwidthMonthliesController@index');
        Route::get('/actions', 'BandwidthMonthlies\BandwidthMonthliesController@getActions');

        Route::get('{s3_bandwidth_monthlies}/tags ', 'BandwidthMonthlies\BandwidthMonthliesController@tags');
        Route::post('{s3_bandwidth_monthlies}/tags ', 'BandwidthMonthlies\BandwidthMonthliesController@saveTags');
        Route::get('{s3_bandwidth_monthlies}/addresses ', 'BandwidthMonthlies\BandwidthMonthliesController@addresses');
        Route::post('{s3_bandwidth_monthlies}/addresses ', 'BandwidthMonthlies\BandwidthMonthliesController@saveAddresses');

        Route::get('/{s3_bandwidth_monthlies}/{subObjects}', 'BandwidthMonthlies\BandwidthMonthliesController@relatedObjects');
        Route::get('/{s3_bandwidth_monthlies}', 'BandwidthMonthlies\BandwidthMonthliesController@show');

        Route::post('/', 'BandwidthMonthlies\BandwidthMonthliesController@store');
        Route::post('/{s3_bandwidth_monthlies}/do/{action}', 'BandwidthMonthlies\BandwidthMonthliesController@doAction');

        Route::patch('/{s3_bandwidth_monthlies}', 'BandwidthMonthlies\BandwidthMonthliesController@update');
        Route::delete('/{s3_bandwidth_monthlies}', 'BandwidthMonthlies\BandwidthMonthliesController@destroy');
    }
);

Route::prefix('notifications-sent')->group(
    function () {
        Route::get('/', 'NotificationsSent\NotificationsSentController@index');
        Route::get('/actions', 'NotificationsSent\NotificationsSentController@getActions');

        Route::get('{s3_notifications_sent}/tags ', 'NotificationsSent\NotificationsSentController@tags');
        Route::post('{s3_notifications_sent}/tags ', 'NotificationsSent\NotificationsSentController@saveTags');
        Route::get('{s3_notifications_sent}/addresses ', 'NotificationsSent\NotificationsSentController@addresses');
        Route::post('{s3_notifications_sent}/addresses ', 'NotificationsSent\NotificationsSentController@saveAddresses');

        Route::get('/{s3_notifications_sent}/{subObjects}', 'NotificationsSent\NotificationsSentController@relatedObjects');
        Route::get('/{s3_notifications_sent}', 'NotificationsSent\NotificationsSentController@show');

        Route::post('/', 'NotificationsSent\NotificationsSentController@store');
        Route::post('/{s3_notifications_sent}/do/{action}', 'NotificationsSent\NotificationsSentController@doAction');

        Route::patch('/{s3_notifications_sent}', 'NotificationsSent\NotificationsSentController@update');
        Route::delete('/{s3_notifications_sent}', 'NotificationsSent\NotificationsSentController@destroy');
    }
);

Route::prefix('audit-logs')->group(
    function () {
        Route::get('/', 'AuditLogs\AuditLogsController@index');
        Route::get('/actions', 'AuditLogs\AuditLogsController@getActions');

        Route::get('{s3_audit_logs}/tags ', 'AuditLogs\AuditLogsController@tags');
        Route::post('{s3_audit_logs}/tags ', 'AuditLogs\AuditLogsController@saveTags');
        Route::get('{s3_audit_logs}/addresses ', 'AuditLogs\AuditLogsController@addresses');
        Route::post('{s3_audit_logs}/addresses ', 'AuditLogs\AuditLogsController@saveAddresses');

        Route::get('/{s3_audit_logs}/{subObjects}', 'AuditLogs\AuditLogsController@relatedObjects');
        Route::get('/{s3_audit_logs}', 'AuditLogs\AuditLogsController@show');

        Route::post('/', 'AuditLogs\AuditLogsController@store');
        Route::post('/{s3_audit_logs}/do/{action}', 'AuditLogs\AuditLogsController@doAction');

        Route::patch('/{s3_audit_logs}', 'AuditLogs\AuditLogsController@update');
        Route::delete('/{s3_audit_logs}', 'AuditLogs\AuditLogsController@destroy');
    }
);

Route::prefix('webhooks')->group(
    function () {
        Route::get('/', 'Webhooks\WebhooksController@index');
        Route::get('/actions', 'Webhooks\WebhooksController@getActions');

        Route::get('{s3_webhooks}/tags ', 'Webhooks\WebhooksController@tags');
        Route::post('{s3_webhooks}/tags ', 'Webhooks\WebhooksController@saveTags');
        Route::get('{s3_webhooks}/addresses ', 'Webhooks\WebhooksController@addresses');
        Route::post('{s3_webhooks}/addresses ', 'Webhooks\WebhooksController@saveAddresses');

        Route::get('/{s3_webhooks}/{subObjects}', 'Webhooks\WebhooksController@relatedObjects');
        Route::get('/{s3_webhooks}', 'Webhooks\WebhooksController@show');

        Route::post('/', 'Webhooks\WebhooksController@store');
        Route::post('/{s3_webhooks}/do/{action}', 'Webhooks\WebhooksController@doAction');

        Route::patch('/{s3_webhooks}', 'Webhooks\WebhooksController@update');
        Route::delete('/{s3_webhooks}', 'Webhooks\WebhooksController@destroy');
    }
);

Route::prefix('webhook-deliveries')->group(
    function () {
        Route::get('/', 'WebhookDeliveries\WebhookDeliveriesController@index');
        Route::get('/actions', 'WebhookDeliveries\WebhookDeliveriesController@getActions');

        Route::get('{s3_webhook_deliveries}/tags ', 'WebhookDeliveries\WebhookDeliveriesController@tags');
        Route::post('{s3_webhook_deliveries}/tags ', 'WebhookDeliveries\WebhookDeliveriesController@saveTags');
        Route::get('{s3_webhook_deliveries}/addresses ', 'WebhookDeliveries\WebhookDeliveriesController@addresses');
        Route::post('{s3_webhook_deliveries}/addresses ', 'WebhookDeliveries\WebhookDeliveriesController@saveAddresses');

        Route::get('/{s3_webhook_deliveries}/{subObjects}', 'WebhookDeliveries\WebhookDeliveriesController@relatedObjects');
        Route::get('/{s3_webhook_deliveries}', 'WebhookDeliveries\WebhookDeliveriesController@show');

        Route::post('/', 'WebhookDeliveries\WebhookDeliveriesController@store');
        Route::post('/{s3_webhook_deliveries}/do/{action}', 'WebhookDeliveries\WebhookDeliveriesController@doAction');

        Route::patch('/{s3_webhook_deliveries}', 'WebhookDeliveries\WebhookDeliveriesController@update');
        Route::delete('/{s3_webhook_deliveries}', 'WebhookDeliveries\WebhookDeliveriesController@destroy');
    }
);

Route::prefix('multipart-uploads')->group(
    function () {
        Route::get('/', 'MultipartUploads\MultipartUploadsController@index');
        Route::get('/actions', 'MultipartUploads\MultipartUploadsController@getActions');

        Route::get('{s3_multipart_uploads}/tags ', 'MultipartUploads\MultipartUploadsController@tags');
        Route::post('{s3_multipart_uploads}/tags ', 'MultipartUploads\MultipartUploadsController@saveTags');
        Route::get('{s3_multipart_uploads}/addresses ', 'MultipartUploads\MultipartUploadsController@addresses');
        Route::post('{s3_multipart_uploads}/addresses ', 'MultipartUploads\MultipartUploadsController@saveAddresses');

        Route::get('/{s3_multipart_uploads}/{subObjects}', 'MultipartUploads\MultipartUploadsController@relatedObjects');
        Route::get('/{s3_multipart_uploads}', 'MultipartUploads\MultipartUploadsController@show');

        Route::post('/', 'MultipartUploads\MultipartUploadsController@store');
        Route::post('/{s3_multipart_uploads}/do/{action}', 'MultipartUploads\MultipartUploadsController@doAction');

        Route::patch('/{s3_multipart_uploads}', 'MultipartUploads\MultipartUploadsController@update');
        Route::delete('/{s3_multipart_uploads}', 'MultipartUploads\MultipartUploadsController@destroy');
    }
);

Route::prefix('worm-commitments')->group(
    function () {
        Route::get('/', 'WormCommitments\WormCommitmentsController@index');
        Route::get('/actions', 'WormCommitments\WormCommitmentsController@getActions');

        Route::get('{s3_worm_commitments}/tags ', 'WormCommitments\WormCommitmentsController@tags');
        Route::post('{s3_worm_commitments}/tags ', 'WormCommitments\WormCommitmentsController@saveTags');
        Route::get('{s3_worm_commitments}/addresses ', 'WormCommitments\WormCommitmentsController@addresses');
        Route::post('{s3_worm_commitments}/addresses ', 'WormCommitments\WormCommitmentsController@saveAddresses');

        Route::get('/{s3_worm_commitments}/{subObjects}', 'WormCommitments\WormCommitmentsController@relatedObjects');
        Route::get('/{s3_worm_commitments}', 'WormCommitments\WormCommitmentsController@show');

        Route::post('/', 'WormCommitments\WormCommitmentsController@store');
        Route::post('/{s3_worm_commitments}/do/{action}', 'WormCommitments\WormCommitmentsController@doAction');

        Route::patch('/{s3_worm_commitments}', 'WormCommitments\WormCommitmentsController@update');
        Route::delete('/{s3_worm_commitments}', 'WormCommitments\WormCommitmentsController@destroy');
    }
);

Route::prefix('deposit-ledgers')->group(
    function () {
        Route::get('/', 'DepositLedgers\DepositLedgersController@index');
        Route::get('/actions', 'DepositLedgers\DepositLedgersController@getActions');

        Route::get('{s3_deposit_ledgers}/tags ', 'DepositLedgers\DepositLedgersController@tags');
        Route::post('{s3_deposit_ledgers}/tags ', 'DepositLedgers\DepositLedgersController@saveTags');
        Route::get('{s3_deposit_ledgers}/addresses ', 'DepositLedgers\DepositLedgersController@addresses');
        Route::post('{s3_deposit_ledgers}/addresses ', 'DepositLedgers\DepositLedgersController@saveAddresses');

        Route::get('/{s3_deposit_ledgers}/{subObjects}', 'DepositLedgers\DepositLedgersController@relatedObjects');
        Route::get('/{s3_deposit_ledgers}', 'DepositLedgers\DepositLedgersController@show');

        Route::post('/', 'DepositLedgers\DepositLedgersController@store');
        Route::post('/{s3_deposit_ledgers}/do/{action}', 'DepositLedgers\DepositLedgersController@doAction');

        Route::patch('/{s3_deposit_ledgers}', 'DepositLedgers\DepositLedgersController@update');
        Route::delete('/{s3_deposit_ledgers}', 'DepositLedgers\DepositLedgersController@destroy');
    }
);

Route::prefix('buckets-perspective')->group(
    function () {
        Route::get('/', 'BucketsPerspective\BucketsPerspectiveController@index');
        Route::get('/actions', 'BucketsPerspective\BucketsPerspectiveController@getActions');

        Route::get('{s3_buckets_perspective}/tags ', 'BucketsPerspective\BucketsPerspectiveController@tags');
        Route::post('{s3_buckets_perspective}/tags ', 'BucketsPerspective\BucketsPerspectiveController@saveTags');
        Route::get('{s3_buckets_perspective}/addresses ', 'BucketsPerspective\BucketsPerspectiveController@addresses');
        Route::post('{s3_buckets_perspective}/addresses ', 'BucketsPerspective\BucketsPerspectiveController@saveAddresses');

        Route::get('/{s3_buckets_perspective}/{subObjects}', 'BucketsPerspective\BucketsPerspectiveController@relatedObjects');
        Route::get('/{s3_buckets_perspective}', 'BucketsPerspective\BucketsPerspectiveController@show');

        Route::post('/', 'BucketsPerspective\BucketsPerspectiveController@store');
        Route::post('/{s3_buckets_perspective}/do/{action}', 'BucketsPerspective\BucketsPerspectiveController@doAction');

        Route::patch('/{s3_buckets_perspective}', 'BucketsPerspective\BucketsPerspectiveController@update');
        Route::delete('/{s3_buckets_perspective}', 'BucketsPerspective\BucketsPerspectiveController@destroy');
    }
);

Route::prefix('access-keys-perspective')->group(
    function () {
        Route::get('/', 'AccessKeysPerspective\AccessKeysPerspectiveController@index');
        Route::get('/actions', 'AccessKeysPerspective\AccessKeysPerspectiveController@getActions');

        Route::get('{s3_access_keys_perspective}/tags ', 'AccessKeysPerspective\AccessKeysPerspectiveController@tags');
        Route::post('{s3_access_keys_perspective}/tags ', 'AccessKeysPerspective\AccessKeysPerspectiveController@saveTags');
        Route::get('{s3_access_keys_perspective}/addresses ', 'AccessKeysPerspective\AccessKeysPerspectiveController@addresses');
        Route::post('{s3_access_keys_perspective}/addresses ', 'AccessKeysPerspective\AccessKeysPerspectiveController@saveAddresses');

        Route::get('/{s3_access_keys_perspective}/{subObjects}', 'AccessKeysPerspective\AccessKeysPerspectiveController@relatedObjects');
        Route::get('/{s3_access_keys_perspective}', 'AccessKeysPerspective\AccessKeysPerspectiveController@show');

        Route::post('/', 'AccessKeysPerspective\AccessKeysPerspectiveController@store');
        Route::post('/{s3_access_keys_perspective}/do/{action}', 'AccessKeysPerspective\AccessKeysPerspectiveController@doAction');

        Route::patch('/{s3_access_keys_perspective}', 'AccessKeysPerspective\AccessKeysPerspectiveController@update');
        Route::delete('/{s3_access_keys_perspective}', 'AccessKeysPerspective\AccessKeysPerspectiveController@destroy');
    }
);

Route::prefix('account-stats')->group(
    function () {
        Route::get('/', 'AccountStats\AccountStatsController@index');
        Route::get('/actions', 'AccountStats\AccountStatsController@getActions');

        Route::get('{s3_account_stats}/tags ', 'AccountStats\AccountStatsController@tags');
        Route::post('{s3_account_stats}/tags ', 'AccountStats\AccountStatsController@saveTags');
        Route::get('{s3_account_stats}/addresses ', 'AccountStats\AccountStatsController@addresses');
        Route::post('{s3_account_stats}/addresses ', 'AccountStats\AccountStatsController@saveAddresses');

        Route::get('/{s3_account_stats}/{subObjects}', 'AccountStats\AccountStatsController@relatedObjects');
        Route::get('/{s3_account_stats}', 'AccountStats\AccountStatsController@show');

        Route::post('/', 'AccountStats\AccountStatsController@store');
        Route::post('/{s3_account_stats}/do/{action}', 'AccountStats\AccountStatsController@doAction');

        Route::patch('/{s3_account_stats}', 'AccountStats\AccountStatsController@update');
        Route::delete('/{s3_account_stats}', 'AccountStats\AccountStatsController@destroy');
    }
);

Route::prefix('accounts-perspective')->group(
    function () {
        Route::get('/', 'AccountsPerspective\AccountsPerspectiveController@index');
        Route::get('/actions', 'AccountsPerspective\AccountsPerspectiveController@getActions');

        Route::get('{s3_accounts_perspective}/tags ', 'AccountsPerspective\AccountsPerspectiveController@tags');
        Route::post('{s3_accounts_perspective}/tags ', 'AccountsPerspective\AccountsPerspectiveController@saveTags');
        Route::get('{s3_accounts_perspective}/addresses ', 'AccountsPerspective\AccountsPerspectiveController@addresses');
        Route::post('{s3_accounts_perspective}/addresses ', 'AccountsPerspective\AccountsPerspectiveController@saveAddresses');

        Route::get('/{s3_accounts_perspective}/{subObjects}', 'AccountsPerspective\AccountsPerspectiveController@relatedObjects');
        Route::get('/{s3_accounts_perspective}', 'AccountsPerspective\AccountsPerspectiveController@show');

        Route::post('/', 'AccountsPerspective\AccountsPerspectiveController@store');
        Route::post('/{s3_accounts_perspective}/do/{action}', 'AccountsPerspective\AccountsPerspectiveController@doAction');

        Route::patch('/{s3_accounts_perspective}', 'AccountsPerspective\AccountsPerspectiveController@update');
        Route::delete('/{s3_accounts_perspective}', 'AccountsPerspective\AccountsPerspectiveController@destroy');
    }
);

Route::prefix('server-capacity-stats')->group(
    function () {
        Route::get('/', 'ServerCapacityStats\ServerCapacityStatsController@index');
        Route::get('/actions', 'ServerCapacityStats\ServerCapacityStatsController@getActions');

        Route::get('{s3_server_capacity_stats}/tags ', 'ServerCapacityStats\ServerCapacityStatsController@tags');
        Route::post('{s3_server_capacity_stats}/tags ', 'ServerCapacityStats\ServerCapacityStatsController@saveTags');
        Route::get('{s3_server_capacity_stats}/addresses ', 'ServerCapacityStats\ServerCapacityStatsController@addresses');
        Route::post('{s3_server_capacity_stats}/addresses ', 'ServerCapacityStats\ServerCapacityStatsController@saveAddresses');

        Route::get('/{s3_server_capacity_stats}/{subObjects}', 'ServerCapacityStats\ServerCapacityStatsController@relatedObjects');
        Route::get('/{s3_server_capacity_stats}', 'ServerCapacityStats\ServerCapacityStatsController@show');

        Route::post('/', 'ServerCapacityStats\ServerCapacityStatsController@store');
        Route::post('/{s3_server_capacity_stats}/do/{action}', 'ServerCapacityStats\ServerCapacityStatsController@doAction');

        Route::patch('/{s3_server_capacity_stats}', 'ServerCapacityStats\ServerCapacityStatsController@update');
        Route::delete('/{s3_server_capacity_stats}', 'ServerCapacityStats\ServerCapacityStatsController@destroy');
    }
);

Route::prefix('servers-perspective')->group(
    function () {
        Route::get('/', 'ServersPerspective\ServersPerspectiveController@index');
        Route::get('/actions', 'ServersPerspective\ServersPerspectiveController@getActions');

        Route::get('{s3_servers_perspective}/tags ', 'ServersPerspective\ServersPerspectiveController@tags');
        Route::post('{s3_servers_perspective}/tags ', 'ServersPerspective\ServersPerspectiveController@saveTags');
        Route::get('{s3_servers_perspective}/addresses ', 'ServersPerspective\ServersPerspectiveController@addresses');
        Route::post('{s3_servers_perspective}/addresses ', 'ServersPerspective\ServersPerspectiveController@saveAddresses');

        Route::get('/{s3_servers_perspective}/{subObjects}', 'ServersPerspective\ServersPerspectiveController@relatedObjects');
        Route::get('/{s3_servers_perspective}', 'ServersPerspective\ServersPerspectiveController@show');

        Route::post('/', 'ServersPerspective\ServersPerspectiveController@store');
        Route::post('/{s3_servers_perspective}/do/{action}', 'ServersPerspective\ServersPerspectiveController@doAction');

        Route::patch('/{s3_servers_perspective}', 'ServersPerspective\ServersPerspectiveController@update');
        Route::delete('/{s3_servers_perspective}', 'ServersPerspective\ServersPerspectiveController@destroy');
    }
);

Route::prefix('usage-daily-stats')->group(
    function () {
        Route::get('/', 'UsageDailyStats\UsageDailyStatsController@index');
        Route::get('/actions', 'UsageDailyStats\UsageDailyStatsController@getActions');

        Route::get('{s3_usage_daily_stats}/tags ', 'UsageDailyStats\UsageDailyStatsController@tags');
        Route::post('{s3_usage_daily_stats}/tags ', 'UsageDailyStats\UsageDailyStatsController@saveTags');
        Route::get('{s3_usage_daily_stats}/addresses ', 'UsageDailyStats\UsageDailyStatsController@addresses');
        Route::post('{s3_usage_daily_stats}/addresses ', 'UsageDailyStats\UsageDailyStatsController@saveAddresses');

        Route::get('/{s3_usage_daily_stats}/{subObjects}', 'UsageDailyStats\UsageDailyStatsController@relatedObjects');
        Route::get('/{s3_usage_daily_stats}', 'UsageDailyStats\UsageDailyStatsController@show');

        Route::post('/', 'UsageDailyStats\UsageDailyStatsController@store');
        Route::post('/{s3_usage_daily_stats}/do/{action}', 'UsageDailyStats\UsageDailyStatsController@doAction');

        Route::patch('/{s3_usage_daily_stats}', 'UsageDailyStats\UsageDailyStatsController@update');
        Route::delete('/{s3_usage_daily_stats}', 'UsageDailyStats\UsageDailyStatsController@destroy');
    }
);

Route::prefix('worm-expiring-perspective')->group(
    function () {
        Route::get('/', 'WormExpiringPerspective\WormExpiringPerspectiveController@index');
        Route::get('/actions', 'WormExpiringPerspective\WormExpiringPerspectiveController@getActions');

        Route::get('{s3_worm_expiring_perspective}/tags ', 'WormExpiringPerspective\WormExpiringPerspectiveController@tags');
        Route::post('{s3_worm_expiring_perspective}/tags ', 'WormExpiringPerspective\WormExpiringPerspectiveController@saveTags');
        Route::get('{s3_worm_expiring_perspective}/addresses ', 'WormExpiringPerspective\WormExpiringPerspectiveController@addresses');
        Route::post('{s3_worm_expiring_perspective}/addresses ', 'WormExpiringPerspective\WormExpiringPerspectiveController@saveAddresses');

        Route::get('/{s3_worm_expiring_perspective}/{subObjects}', 'WormExpiringPerspective\WormExpiringPerspectiveController@relatedObjects');
        Route::get('/{s3_worm_expiring_perspective}', 'WormExpiringPerspective\WormExpiringPerspectiveController@show');

        Route::post('/', 'WormExpiringPerspective\WormExpiringPerspectiveController@store');
        Route::post('/{s3_worm_expiring_perspective}/do/{action}', 'WormExpiringPerspective\WormExpiringPerspectiveController@doAction');

        Route::patch('/{s3_worm_expiring_perspective}', 'WormExpiringPerspective\WormExpiringPerspectiveController@update');
        Route::delete('/{s3_worm_expiring_perspective}', 'WormExpiringPerspective\WormExpiringPerspectiveController@destroy');
    }
);

Route::prefix('quota-alerts-perspective')->group(
    function () {
        Route::get('/', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@index');
        Route::get('/actions', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@getActions');

        Route::get('{s3_quota_alerts_perspective}/tags ', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@tags');
        Route::post('{s3_quota_alerts_perspective}/tags ', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@saveTags');
        Route::get('{s3_quota_alerts_perspective}/addresses ', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@addresses');
        Route::post('{s3_quota_alerts_perspective}/addresses ', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@saveAddresses');

        Route::get('/{s3_quota_alerts_perspective}/{subObjects}', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@relatedObjects');
        Route::get('/{s3_quota_alerts_perspective}', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@show');

        Route::post('/', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@store');
        Route::post('/{s3_quota_alerts_perspective}/do/{action}', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@doAction');

        Route::patch('/{s3_quota_alerts_perspective}', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@update');
        Route::delete('/{s3_quota_alerts_perspective}', 'QuotaAlertsPerspective\QuotaAlertsPerspectiveController@destroy');
    }
);

Route::prefix('backup-agents')->group(
    function () {
        Route::get('/', 'BackupAgents\BackupAgentsController@index');
        Route::get('/actions', 'BackupAgents\BackupAgentsController@getActions');

        Route::get('{s3_backup_agents}/tags ', 'BackupAgents\BackupAgentsController@tags');
        Route::post('{s3_backup_agents}/tags ', 'BackupAgents\BackupAgentsController@saveTags');
        Route::get('{s3_backup_agents}/addresses ', 'BackupAgents\BackupAgentsController@addresses');
        Route::post('{s3_backup_agents}/addresses ', 'BackupAgents\BackupAgentsController@saveAddresses');

        Route::get('/{s3_backup_agents}/{subObjects}', 'BackupAgents\BackupAgentsController@relatedObjects');
        Route::get('/{s3_backup_agents}', 'BackupAgents\BackupAgentsController@show');

        // POST /s3/backup-agents/ issues a registration token — it does not create
        // an active agent. See BackupAgentsService::create().
        Route::post('/', 'BackupAgents\BackupAgentsController@store');
        Route::post('/{s3_backup_agents}/do/{action}', 'BackupAgents\BackupAgentsController@doAction');

        Route::patch('/{s3_backup_agents}', 'BackupAgents\BackupAgentsController@update');
        Route::delete('/{s3_backup_agents}', 'BackupAgents\BackupAgentsController@destroy');
    }
);

Route::prefix('backup-jobs')->group(
    function () {
        Route::get('/', 'BackupJobs\BackupJobsController@index');
        Route::get('/actions', 'BackupJobs\BackupJobsController@getActions');

        Route::get('{s3_backup_jobs}/tags ', 'BackupJobs\BackupJobsController@tags');
        Route::post('{s3_backup_jobs}/tags ', 'BackupJobs\BackupJobsController@saveTags');
        Route::get('{s3_backup_jobs}/addresses ', 'BackupJobs\BackupJobsController@addresses');
        Route::post('{s3_backup_jobs}/addresses ', 'BackupJobs\BackupJobsController@saveAddresses');

        Route::get('/{s3_backup_jobs}/{subObjects}', 'BackupJobs\BackupJobsController@relatedObjects');
        Route::get('/{s3_backup_jobs}', 'BackupJobs\BackupJobsController@show');

        Route::post('/', 'BackupJobs\BackupJobsController@store');
        Route::post('/{s3_backup_jobs}/do/{action}', 'BackupJobs\BackupJobsController@doAction');

        Route::patch('/{s3_backup_jobs}', 'BackupJobs\BackupJobsController@update');
        Route::delete('/{s3_backup_jobs}', 'BackupJobs\BackupJobsController@destroy');
    }
);

Route::prefix('backup-job-runs')->group(
    function () {
        // Read-only — see BackupJobRunsController's class docblock.
        Route::get('/', 'BackupJobRuns\BackupJobRunsController@index');
        Route::get('/{s3_backup_job_runs}/{subObjects}', 'BackupJobRuns\BackupJobRunsController@relatedObjects');
        Route::get('/{s3_backup_job_runs}', 'BackupJobRuns\BackupJobRunsController@show');
    }
);

Route::prefix('restore-jobs')->group(
    function () {
        // Read-only — see RestoreJobsController's class docblock. Triggering a
        // restore happens via POST /backup-jobs/{id}/restore below.
        Route::get('/', 'RestoreJobs\RestoreJobsController@index');
        Route::get('/{s3_restore_jobs}/{subObjects}', 'RestoreJobs\RestoreJobsController@relatedObjects');
        Route::get('/{s3_restore_jobs}', 'RestoreJobs\RestoreJobsController@show');
    }
);

// EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

Route::prefix('backup-agents')->group(
    function () {
        // POST (not GET) so it doesn't collide with the {subObjects} wildcard route
        // registered above, same reasoning as access-keys/reveal below.
        Route::post('/{s3_backup_agents}/revoke', 'BackupAgents\BackupAgentsController@revoke');
    }
);

Route::prefix('backup-jobs')->group(
    function () {
        Route::post('/{s3_backup_jobs}/run-now', 'BackupJobs\BackupJobsController@runNow');
        Route::post('/{s3_backup_jobs}/restore', 'BackupJobs\BackupJobsController@restore');
    }
);

Route::prefix('access-keys')->group(
    function () {
        // POST (not GET) so it doesn't collide with the {subObjects} wildcard route
        // registered above, which would otherwise intercept it and return a false 404.
        Route::post('/{s3_access_keys}/reveal', 'AccessKeys\AccessKeysController@reveal');
    }
);

Route::prefix('bandwidth-monthlies')->group(
    function () {
        Route::get('/', 'BandwidthMonthlies\BandwidthMonthliesController@index');
        Route::get('/actions', 'BandwidthMonthlies\BandwidthMonthliesController@getActions');

        Route::get('{s3_bandwidth_monthlies}/tags ', 'BandwidthMonthlies\BandwidthMonthliesController@tags');
        Route::post('{s3_bandwidth_monthlies}/tags ', 'BandwidthMonthlies\BandwidthMonthliesController@saveTags');
        Route::get('{s3_bandwidth_monthlies}/addresses ', 'BandwidthMonthlies\BandwidthMonthliesController@addresses');
        Route::post('{s3_bandwidth_monthlies}/addresses ', 'BandwidthMonthlies\BandwidthMonthliesController@saveAddresses');

        Route::get('/{s3_bandwidth_monthlies}/{subObjects}', 'BandwidthMonthlies\BandwidthMonthliesController@relatedObjects');
        Route::get('/{s3_bandwidth_monthlies}', 'BandwidthMonthlies\BandwidthMonthliesController@show');

        Route::post('/', 'BandwidthMonthlies\BandwidthMonthliesController@store');
        Route::post('/{s3_bandwidth_monthlies}/do/{action}', 'BandwidthMonthlies\BandwidthMonthliesController@doAction');

        Route::patch('/{s3_bandwidth_monthlies}', 'BandwidthMonthlies\BandwidthMonthliesController@update');
        Route::delete('/{s3_bandwidth_monthlies}', 'BandwidthMonthlies\BandwidthMonthliesController@destroy');
    }
);

});
