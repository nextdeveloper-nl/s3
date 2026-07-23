<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Str;
use NextDeveloper\S3\Services\AbstractServices\AbstractServersService;

/**
 * This class is responsible from managing the data for Servers
 *
 * Class ServersService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class ServersService extends AbstractServersService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Register a new storage server and generate its initial API key.
     * Also provisions its Marketplace packaging via ensurePackaging() so
     * every server is immediately sellable.
     */
    public static function create(array $data)
    {
        $data['agent_api_key'] = Str::random(64);
        $data['agent_status']  = 'pending';

        $server = parent::create($data);

        return self::ensurePackaging($server);
    }

    /**
     * Idempotently ensures a server has its 1:1 Marketplace Product, a
     * public Pay-As-You-Go catalog template priced at the server's own
     * price_per_gb, and the configured set of default paid tiers (see
     * config('s3.packaging.default_tiers')) — so every server is
     * immediately sellable and packages can be priced per-server (see
     * AccountsService::subscribeToServerPackage()).
     *
     * Every step checks for an existing row (keyed by sku, unique per
     * server since each is suffixed with the server's uuid) before
     * creating one, so this is safe to call repeatedly: once for new
     * servers via create() above, and again later as a backfill for
     * servers that predate packaging or were seeded outside create() (see
     * EnsureServerPackagingJob) — and safe to re-run any time a new
     * default tier is added to config, to roll it out to existing servers.
     */
    public static function ensurePackaging(\NextDeveloper\S3\Database\Models\Servers $server): \NextDeveloper\S3\Database\Models\Servers
    {
        if (!$server->marketplace_product_id) {
            $marketId = \NextDeveloper\Commons\Helpers\DatabaseHelper::uuidToId(
                '\NextDeveloper\Marketplace\Database\Models\Markets',
                config('s3.packaging.marketplace_market_id')
            );

            if (!$marketId) {
                throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
                    'S3_PACKAGING_MARKETPLACE_MARKET_ID is not configured (or does not match a real marketplace_markets row) — ' .
                    'cannot provision this server\'s Marketplace product without a sales channel to attach it to.'
                );
            }

            $product = \NextDeveloper\Marketplace\Database\Models\Products::create([
                'name'                   => 'S3 Storage — ' . $server->hostname,
                'slug'                   => 's3-storage-' . $server->uuid,
                // marketplace_products.product_type is a fixed Postgres
                // enum — 'service' matches is_service=true below and is
                // already a valid label; the S3-specific distinction lives
                // in slug/name instead, nothing reads product_type for S3.
                'product_type'           => 'service',
                'is_service'             => true,
                'is_public'              => true,
                'is_active'              => true,
                'marketplace_market_id'  => $marketId,
                // marketplace_products.iam_account_id/iam_user_id are
                // NOT NULL — this is a platform-sellable product, not a
                // customer's, so it's owned by whichever account/user
                // registered the server, same as the server row itself.
                'iam_account_id'         => $server->iam_account_id,
                'iam_user_id'            => $server->iam_user_id,
            ]);

            $server->update(['marketplace_product_id' => $product->id]);
            $server = $server->fresh();
        }

        $existingSkus = \NextDeveloper\Marketplace\Database\Models\ProductCatalogs::withoutGlobalScopes()
            ->where('marketplace_product_id', $server->marketplace_product_id)
            ->pluck('sku')
            ->all();

        $payGSku = 's3-payg-' . $server->uuid;

        if (!in_array($payGSku, $existingSkus, true)) {
            \NextDeveloper\Marketplace\Database\Models\ProductCatalogs::create([
                'marketplace_product_id' => $server->marketplace_product_id,
                'name'      => 'Pay As You Go',
                'sku'       => $payGSku,
                'price'     => 0,
                'common_currency_id' => $server->common_currency_id,
                // Public, sellable template — every customer who subscribes
                // gets their own Marketplace\Subscriptions row pointing
                // straight at this template (see
                // AccountsService::subscribeToServerPackage()); nothing is
                // ever cloned out of this table.
                'is_public' => true,
                'args'      => [
                    'included_storage_bytes'       => 0,
                    'overage_price_per_gb_storage' => $server->price_per_gb ?? 0,
                    'billing_model' => 's3-flat-plus-overage',
                ],
                'features'  => ['Billed per GB used, no fixed fee'],
            ]);
        }

        foreach (config('s3.packaging.default_tiers', []) as $tier) {
            $sku = $tier['sku'] . '-' . $server->uuid;

            if (in_array($sku, $existingSkus, true)) {
                continue;
            }

            $includedGb  = $tier['included_storage_bytes'] / 1073741824;
            $pricePerGb  = $server->price_per_gb ?? 0;
            $flatFee     = round($includedGb * $pricePerGb * ($tier['flat_fee_multiplier'] ?? 1), 2);
            $overageRate = round($pricePerGb * (($tier['overage_discount_percent'] ?? 100) / 100), 6);

            \NextDeveloper\Marketplace\Database\Models\ProductCatalogs::create([
                'marketplace_product_id' => $server->marketplace_product_id,
                'name'      => $tier['name'],
                'sku'       => $sku,
                'price'     => $flatFee,
                'common_currency_id' => $server->common_currency_id,
                'is_public' => true,
                'args'      => [
                    'included_storage_bytes'       => $tier['included_storage_bytes'],
                    'overage_price_per_gb_storage' => $overageRate,
                    'billing_model' => 's3-flat-plus-overage',
                ],
                'features'  => [
                    number_format($includedGb) . ' GB included storage',
                    'Overage billed per GB beyond that',
                ],
            ]);
        }

        return $server->fresh();
    }

    /**
     * Rotate the agent API key for a server (e.g. after a security incident).
     */
    public static function regenerateApiKey(string $uuid): \NextDeveloper\S3\Database\Models\Servers
    {
        $model = \NextDeveloper\S3\Database\Models\Servers::where('uuid', $uuid)->firstOrFail();
        $model->update(['agent_api_key' => Str::random(64)]);

        return $model->fresh();
    }

    /**
     * Update server health fields from a storaged telemetry payload.
     *
     * @param  string  $uuid
     * @param  array   $telemetry  Fields: master_reachable, volumes_degraded, capacity_pct, agent_version, etc.
     */
    public static function updateHealthFromTelemetry(string $uuid, array $telemetry): void
    {
        $model = \NextDeveloper\S3\Database\Models\Servers::where('uuid', $uuid)->first();
        if (!$model) {
            return;
        }

        $capacityPct = isset($telemetry['capacity_bytes_total']) && $telemetry['capacity_bytes_total'] > 0
            ? ($telemetry['capacity_bytes_used'] / $telemetry['capacity_bytes_total']) * 100
            : 0;

        $health = 'healthy';
        if (($telemetry['volumes_degraded'] ?? 0) > 0) {
            $health = 'degraded';
        }
        if (!($telemetry['master_reachable'] ?? true)) {
            $health = 'unreachable';
        }
        if ($capacityPct >= 90) {
            $health = 'critical';
        } elseif ($capacityPct >= 80 && $health === 'healthy') {
            $health = 'warning';
        }

        $update = [
            'health'             => $health,
            'agent_last_seen_at' => now(),
            'agent_status'       => 'connected',
        ];

        if (!empty($telemetry['agent_version'])) {
            $update['agent_version'] = $telemetry['agent_version'];
        }
        if (!empty($telemetry['seaweedfs_version'])) {
            $update['seaweedfs_version'] = $telemetry['seaweedfs_version'];
        }

        // Store component health as JSONB
        $components = $model->components ?? [];
        foreach (['master', 'volume', 'filer', 's3'] as $component) {
            if (array_key_exists("{$component}_reachable", $telemetry)) {
                $components[$component] = ['reachable' => $telemetry["{$component}_reachable"]];
            }
        }
        if (!empty($components)) {
            $update['components'] = $components;
        }

        $model->update($update);
    }

    /**
     * Mark a server as disconnected when its SSE stream closes.
     */
    public static function markDisconnected(string $uuid): void
    {
        $model = \NextDeveloper\S3\Database\Models\Servers::where('uuid', $uuid)->first();
        if ($model) {
            $model->update(['agent_status' => 'disconnected']);
        }
    }
}
