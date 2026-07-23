# Update: server packages no longer clone a catalog row on subscribe

**Date:** 2026-07-23
**Backend:** `nextdeveloper/s3` + `plusclouds.api.v4` main app
**Affects:** panel (frontend) — nothing breaking, but two response shapes
got more correct/useful. See below before relying on old assumptions.

## What changed and why

The original design (`../packaging-billing-ui-specification.md`, prior
revision) had `subscribeToServerPackage()` clone the chosen public package
template into a private, per-customer `ProductCatalogs` row
(`is_public=false`) on every subscribe. That clone did two jobs at once: it
froze the price/allowance at subscribe time (so a later admin price edit
couldn't retroactively reprice existing customers), and it gave the storage
overage invoice line a distinct object identity to key against, separate
from the flat-fee line's `Marketplace\Subscriptions` row — needed because
invoice line lookup is keyed by `(object_type, object_id)` of whatever model
a billing class is constructed with, and reusing one row for two lines would
make the second overwrite the first.

That clone had a real cost: `marketplace_product_catalogs` accumulated one
extra row per (account, server) subscription forever, every admin/list
screen touching that table had to defensively filter `is_public=true` or
explain away the clones, and — unnoticed until now — the "Current Plan"
badge match described in §5.1 was actually broken, since `my_active_package.
product_catalog_id` pointed at the clone's own uuid, which by construction
never matches any `is_public=true` template's uuid in the picker list.

The fix: `subscribeToServerPackage()` now creates **two plain
`Marketplace\Subscriptions` rows** per (account, server), both pointing
directly at the public template (no clone), distinguished by a `role` key
(`'fee'` | `'overage'`) inside a `subscription_data` JSON snapshot on each
row. Two distinct `Subscriptions` ids solve the invoice-line collision
without touching shared `AbstractInvoiceItem` code; `subscription_data`
solves the price freeze without touching `product-catalogs` at all.

## What this means for the frontend

- **Nothing you built against §3–§5 needs to change.** `my_active_package`
  on the Servers list/detail response has the same shape
  (`product_catalog_id`, `name`, `price`, `args`) — it's just computed
  differently now (see `AccountsService::getActivePackageForServer()`).
- **The "Current Plan" badge match in §5.1 now actually works** —
  `product_catalog_id` is the real public template's id, so comparing it
  against the `is_public=true` picker list correctly finds a match. If you
  had already worked around this (e.g. matching on `name` instead), you can
  drop the workaround.
- **`GET /marketplace/product-catalogs` for a server will never show
  private/customer rows again** — the defensive filtering and "Customer
  copy — not editable" warning described in the prior §6 revision are gone.
  Every row returned for a server's `marketplace_product_id` is a real,
  admin-editable, sellable package.
