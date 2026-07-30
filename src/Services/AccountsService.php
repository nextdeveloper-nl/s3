<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\AbstractServices\AbstractAccountsService;

/**
 * This class is responsible from managing the data for Accounts
 *
 * Class AccountsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class AccountsService extends AbstractAccountsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Create an S3 account. Enforces unique slug and sets defaults.
     */
    public static function create(array $data)
    {
        // Ensure slug is unique across non-deleted accounts
        if (!empty($data['slug'])) {
            $exists = \NextDeveloper\S3\Database\Models\Accounts::where('slug', $data['slug'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
                    "An S3 account with slug '{$data['slug']}' already exists."
                );
            }
        }

        // Default quota values (can be overridden per account)
        $data['status'] = 'active';
        $data['quota_storage_bytes']  = $data['quota_storage_bytes']  ?? config('s3.defaults.quota_storage_bytes', 10737418240);  // 10 GB
        $data['quota_egress_bytes_mo'] = $data['quota_egress_bytes_mo'] ?? config('s3.defaults.quota_egress_bytes_mo', 107374182400); // 100 GB
        $data['quota_max_buckets']    = $data['quota_max_buckets']    ?? config('s3.defaults.quota_max_buckets', 10);
        $data['quota_max_objects']    = $data['quota_max_objects']    ?? config('s3.defaults.quota_max_objects', 1000000);

        return parent::create($data);
    }

    /**
     * Block an account: marks it blocked in the DB then pushes customer_block
     * to every connected agent server where the account has active buckets.
     */
    public static function block(string $uuid, string $reason): \NextDeveloper\S3\Database\Models\Accounts
    {
        $model = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $model->update([
            'status'         => 'blocked',
            'blocked_at'     => now(),
            'blocked_reason' => $reason,
        ]);

        // Suspend the tenant on every agent that hosts their buckets.
        static::dispatchToAccountServers($model, 'block');

        AuditLogsService::log('account.block', UserHelper::me()->uuid ?? 'system', [
            'iam_account_id' => $model->iam_account_id,
            's3_account_id'  => $model->id,
            'reason'         => $reason,
        ]);

        return $model->fresh();
    }

    /**
     * Unblock an account: restores active status in the DB then pushes
     * customer_unblock to every connected agent server.
     */
    public static function unblock(string $uuid): \NextDeveloper\S3\Database\Models\Accounts
    {
        $model = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $model->update([
            'status'         => 'active',
            'blocked_at'     => null,
            'blocked_reason' => null,
        ]);

        // Restore the tenant's keys on every agent that hosts their buckets.
        static::dispatchToAccountServers($model, 'unblock');

        AuditLogsService::log('account.unblock', UserHelper::me()->uuid ?? 'system', [
            'iam_account_id' => $model->iam_account_id,
            's3_account_id'  => $model->id,
        ]);

        return $model->fresh();
    }

    /**
     * Suspends the S3 account belonging to the given IAM account, if one
     * exists. S3 accounts are opt-in (created on first subscription), so a
     * customer without an S3 account is a no-op here, not an error.
     */
    public static function suspendWithIamAccount(\NextDeveloper\IAM\Database\Models\Accounts $account): ?\NextDeveloper\S3\Database\Models\Accounts
    {
        $s3Account = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()
            ->where('iam_account_id', $account->id)
            ->first();

        if (!$s3Account) {
            return null;
        }

        return self::block($s3Account->uuid, 'Account suspended.');
    }

    /**
     * Subscribes an S3 account to a package (a Marketplace ProductCatalogs
     * template) sold on a specific server.
     *
     * "Subscribing" no longer clones the public template into a private
     * catalog row. Instead it creates TWO Marketplace\Subscriptions rows
     * pointing directly at the public template, distinguished by a
     * 'role' key inside subscription_data ('fee' | 'overage'):
     *   - the 'fee' row bills the flat monthly price (SubscriptionBilling,
     *     unchanged, generic to every Marketplace product)
     *   - the 'overage' row is a pure identity anchor for
     *     S3ServerStorageOverageBilling
     * Both rows carry a frozen snapshot of the template's price/args/name/
     * sku/common_currency_id in subscription_data — this is the price-lock
     * (mirrors s3_worm_commitments.price_per_gb_mo's
     * snapshot-at-commitment-time precedent: an admin editing the public
     * template later must not reprice existing subscribers).
     *
     * Two rows, not one, because AbstractInvoiceItem::setItemCost()
     * finds-or-creates an invoice line by (object_type, object_id, invoice)
     * of whatever model a billing class is constructed with. Both rows are
     * Subscriptions (same object_type), but each has its own id, so the
     * flat-fee line and the overage line never collide — no clone, and no
     * change needed to the shared AbstractInvoiceItem keying.
     *
     * doAction() routes bypass the coarse per-role Authorize middleware
     * check (URI has more than 2 segments), so a customer can already reach
     * this method on their own account via existing row-level scoping. Every
     * privileged write below runs under a temporarily-elevated admin
     * context (UserHelper::setAdminAsCurrentUser()) so it doesn't need
     * S3UserRole to be granted s3_accounts:update (which would also expose
     * unrelated fields AccountsUpdateRequest already allows, like
     * quota_storage_bytes/status, to direct customer PATCH). The elevation
     * only bypasses the permission check — iam_account_id/iam_user_id on
     * every created row are still set explicitly to the customer's own ids.
     *
     * @return array{fee_subscription: \NextDeveloper\Marketplace\Database\Models\Subscriptions, overage_subscription: \NextDeveloper\Marketplace\Database\Models\Subscriptions}
     */
    public static function subscribeToServerPackage(string $accountUuid, string $serverUuid, string $templateCatalogUuid): array
    {
        $account = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()
            ->where('uuid', $accountUuid)
            ->firstOrFail();

        $server = Servers::withoutGlobalScopes()
            ->where('uuid', $serverUuid)
            ->firstOrFail();

        $template = \NextDeveloper\Marketplace\Database\Models\ProductCatalogs::withoutGlobalScopes()
            ->where('uuid', $templateCatalogUuid)
            ->where('is_public', true)
            ->firstOrFail();

        // The template must belong to *this* server's own product — prevents
        // subscribing to a package priced for a different server.
        if ($template->marketplace_product_id !== $server->marketplace_product_id) {
            throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
                'This package is not sold for the selected server.'
            );
        }

        $callingUser    = UserHelper::me();
        $callingAccount = UserHelper::currentAccount();
        UserHelper::setAdminAsCurrentUser();

        try {
            // Close out every existing active subscription (both the 'fee'
            // and 'overage' rows) for this (account, server) pair — matched
            // via the linked catalog's marketplace_product_id, since that's
            // the server-scoping key regardless of which specific package
            // template within that server they were subscribed under.
            $existingSubscriptions = \NextDeveloper\Marketplace\Database\Models\Subscriptions::withoutGlobalScopes()
                ->where('iam_account_id', $account->iam_account_id)
                ->where('is_valid', true)
                ->whereHas('productCatalogs', function ($q) use ($server) {
                    $q->where('marketplace_product_id', $server->marketplace_product_id);
                })
                ->get();

            foreach ($existingSubscriptions as $existing) {
                $existing->update(['is_valid' => false, 'subscription_ends_at' => now()]);
            }

            // Frozen snapshot written into both new rows' subscription_data
            // — future edits to the public template never affect a
            // customer already subscribed.
            $snapshot = [
                'price'              => $template->price,
                'args'               => $template->args,
                'name'               => $template->name,
                'sku'                => $template->sku,
                'common_currency_id' => $template->common_currency_id,
            ];

            $feeSubscription = \NextDeveloper\Marketplace\Database\Models\Subscriptions::create([
                'marketplace_product_catalog_id' => $template->id,
                'iam_account_id'         => $account->iam_account_id, // customer's own id, not the elevated admin's
                'iam_user_id'            => $account->iam_user_id,
                'subscription_starts_at' => now(),
                'is_valid'               => true,
                'subscription_data'      => array_merge($snapshot, ['role' => 'fee']),
            ]);

            $overageSubscription = \NextDeveloper\Marketplace\Database\Models\Subscriptions::create([
                'marketplace_product_catalog_id' => $template->id,
                'iam_account_id'         => $account->iam_account_id,
                'iam_user_id'            => $account->iam_user_id,
                'subscription_starts_at' => now(),
                'is_valid'               => true,
                'subscription_data'      => array_merge($snapshot, ['role' => 'overage']),
            ]);

            // Raise the account-wide storage hard-ceiling to a safety
            // multiple of the SUM of included storage across every
            // currently-valid 'fee' subscription this account holds — never
            // lower it. quota_storage_bytes keeps its existing meaning (the
            // hard abuse ceiling QuotaHelper/CheckQuotasJob block on).
            $includedStorageTotal = \NextDeveloper\Marketplace\Database\Models\Subscriptions::withoutGlobalScopes()
                ->where('iam_account_id', $account->iam_account_id)
                ->where('is_valid', true)
                ->where('subscription_data->role', 'fee')
                ->get()
                ->sum(fn($s) => (int) ($s->subscription_data['args']['included_storage_bytes'] ?? 0));

            $safetyMultiplier = config('s3.packaging.storage_quota_safety_multiplier', 3);

            $account->update([
                'quota_storage_bytes' => max($account->quota_storage_bytes, (int) ($includedStorageTotal * $safetyMultiplier)),
            ]);

            // First server package this account has ever bought also
            // unlocks the account-wide egress overage baseline (idempotent
            // — never re-lowers it once set).
            if ($account->included_egress_bytes_mo <= 0) {
                $defaultIncludedEgress  = config('s3.packaging.default_included_egress_bytes_mo', 0);
                $egressSafetyMultiplier = config('s3.packaging.egress_quota_safety_multiplier', 3);

                $account->update([
                    'included_egress_bytes_mo' => $defaultIncludedEgress,
                    'quota_egress_bytes_mo'    => max($account->quota_egress_bytes_mo, (int) ($defaultIncludedEgress * $egressSafetyMultiplier)),
                ]);
            }

            AuditLogsService::log('account.subscribe_server_package', $callingUser->uuid ?? 'system', [
                'iam_account_id' => $account->iam_account_id,
                's3_account_id'  => $account->id,
                'reason'         => 'Subscribed to catalog ' . $template->uuid . ' on server ' . $server->uuid
                                     . ' (fee subscription: ' . $feeSubscription->uuid
                                     . ', overage subscription: ' . $overageSubscription->uuid . ')',
            ]);

            return [
                'fee_subscription'     => $feeSubscription->fresh(),
                'overage_subscription' => $overageSubscription->fresh(),
            ];
        } finally {
            // Always restore the original caller, even if something above threw.
            UserHelper::setCurrentUserAndAccount($callingUser, $callingAccount);
        }
    }

    /**
     * Resolves the given IAM account's currently active package on a
     * specific server, if any — the 'fee' role Subscriptions row created by
     * subscribeToServerPackage(). Returns null if the account has never
     * subscribed on this server (customers aren't auto-subscribed to
     * Pay-As-You-Go — it's just another package they opt into like any
     * other, so this works identically for a paid tier or PAYG).
     *
     * Takes iam_account_id directly rather than an S3 account uuid because
     * a customer can browse servers/packages before an S3 Accounts row
     * exists (S3 accounts are opt-in, created on first subscription) — no
     * point forcing that lookup just to answer "have they subscribed yet".
     *
     * product_catalog_id in the returned array is the PUBLIC template's
     * uuid (not a private clone, since none exists anymore) — this is what
     * lets a UI package picker match it against `GET
     * /marketplace/product-catalogs?filter[is_public]=true` results to show
     * a "Current Plan" badge.
     */
    public static function getActivePackageForServer(int $iamAccountId, Servers $server): ?array
    {
        if (!$server->marketplace_product_id) {
            return null;
        }

        $feeSubscription = \NextDeveloper\Marketplace\Database\Models\Subscriptions::withoutGlobalScopes()
            ->where('iam_account_id', $iamAccountId)
            ->where('is_valid', true)
            ->where('subscription_data->role', 'fee')
            ->whereHas('productCatalogs', function ($q) use ($server) {
                $q->where('marketplace_product_id', $server->marketplace_product_id);
            })
            ->first();

        if (!$feeSubscription) {
            return null;
        }

        $data = $feeSubscription->subscription_data ?? [];
        $catalog = \NextDeveloper\Marketplace\Database\Models\ProductCatalogs::withoutGlobalScopes()
            ->where('id', $feeSubscription->marketplace_product_catalog_id)
            ->first();

        return [
            'subscription_id'    => $feeSubscription->uuid,
            'product_catalog_id' => $catalog->uuid ?? null,
            'name'  => $data['name']  ?? null,
            'price' => $data['price'] ?? null,
            'args'  => $data['args']  ?? null,
        ];
    }

    /**
     * Idempotently ensures the account has at least an active subscription
     * (any role/tier) on the given server — auto-subscribing to the
     * server's Pay-As-You-Go catalog entry (sku 's3-payg-{server_uuid}',
     * provisioned by ServersService::ensurePackaging()) if none exists yet.
     * No-op if the account already holds an active 'fee' subscription on
     * this server, whether PAYG or a paid tier — never touches an existing
     * plan.
     *
     * Soft/logging variant of ensurePaygSubscriptionOrFail() — swallows the
     * "server isn't sellable" case instead of throwing, since this is used
     * by EnsureAccountPaygSubscriptionJob's backfill, where one unsellable
     * server must not abort the whole run. BucketsService::create() uses
     * the throwing variant instead, to hard-block bucket creation.
     */
    public static function ensurePaygSubscription(\NextDeveloper\S3\Database\Models\Accounts $account, Servers $server): void
    {
        try {
            self::ensurePaygSubscriptionOrFail($account, $server);
        } catch (\NextDeveloper\Commons\Exceptions\NotAllowedException $e) {
            \Illuminate\Support\Facades\Log::warning('[AccountsService] Could not auto-subscribe account to PAYG', [
                'account_uuid' => $account->uuid,
                'server_uuid'  => $server->uuid,
                'reason'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Same as ensurePaygSubscription() but throws NotAllowedException
     * instead of silently skipping when there is no way to establish a
     * billing anchor — used by BucketsService::create() to hard-block
     * bucket creation on a server with no product attached, or no
     * Pay-As-You-Go catalog entry, rather than letting usage accrue
     * unbilled.
     */
    public static function ensurePaygSubscriptionOrFail(\NextDeveloper\S3\Database\Models\Accounts $account, Servers $server): void
    {
        if (self::getActivePackageForServer($account->iam_account_id, $server)) {
            return;
        }

        if (!$server->marketplace_product_id) {
            throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
                'This server has no Marketplace product attached — buckets cannot be created on it until it is packaged for sale.'
            );
        }

        $paygCatalog = \NextDeveloper\Marketplace\Database\Models\ProductCatalogs::withoutGlobalScopes()
            ->where('marketplace_product_id', $server->marketplace_product_id)
            ->where('sku', 's3-payg-' . $server->uuid)
            ->first();

        if (!$paygCatalog) {
            throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
                'This server has no Pay-As-You-Go package configured — buckets cannot be created on it until one exists.'
            );
        }

        self::subscribeToServerPackage($account->uuid, $server->uuid, $paygCatalog->uuid);
    }

    /**
     * Dispatch customer_block or customer_unblock to every connected agent
     * server that hosts at least one active bucket for this account.
     */
    private static function dispatchToAccountServers(
        \NextDeveloper\S3\Database\Models\Accounts $account,
        string $operation
    ): void {
        $serverIds = Buckets::withoutGlobalScopes()
            ->where('s3_account_id', $account->id)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('s3_server_id');

        if ($serverIds->isEmpty()) {
            return;
        }

        $servers = Servers::withoutGlobalScopes()
            ->whereIn('id', $serverIds)
            ->where('agent_status', 'connected')
            ->get();

        foreach ($servers as $server) {
            if ($operation === 'block') {
                S3AgentCommandService::customerBlock($server->uuid, $account->uuid);
            } else {
                S3AgentCommandService::customerUnblock($server->uuid, $account->uuid);
            }
        }
    }
}