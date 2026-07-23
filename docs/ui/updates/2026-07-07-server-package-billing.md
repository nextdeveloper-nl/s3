# Update: per-server storage packages and overage billing are new

**Date:** 2026-07-07
**Backend:** `nextdeveloper/s3` (post v1.1.27) + `plusclouds.api.v4` main app
**Affects:** panel (frontend) — Servers list/detail, Dashboard, a new "My Storage
Plan(s)" page, and a new admin "Packages" tab on Server Detail

## What changed and why

The S3 module had no concept of a sellable plan — every account got one flat
set of quota defaults from env config, and there was no way to sell "1TB for
$20/month" or a metered pay-as-you-go plan, let alone actually bill for it.

Storage pricing needed to vary **per server** (`s3_servers.price_per_gb`
already varies per physical node, and a bucket is already tied to exactly one
server), which ruled out a single account-wide plan. The design landed on:
one Marketplace Product per server, one or more priced "packages"
(`ProductCatalogs` rows) per product, and a **clone-on-subscribe** mechanism —
subscribing clones the chosen public package template into a private,
frozen-price row scoped to that customer, which is also what the new overage
billing keys its invoice line on. No new S3-specific table was introduced —
this reuses Marketplace's existing `product-catalogs`/`subscriptions`
resources end to end.

Full field-by-field spec is in the new companion doc
`../packaging-billing-ui-specification.md` — this note is the short version
plus the reasoning, same structure as `2026-07-05-access-key-name-and-bucket-acls.md`.

## What to build

- **Servers list/detail:** two new fields per server — `marketplace_product_id`
  (used to fetch that server's packages, not shown directly) and
  `my_active_package` (the current account's active plan on that server, or
  `null`). See spec §4.
- **Package Picker + Subscribe/Change:** a new screen reachable from each
  server row, listing that server's public packages
  (`GET /marketplace/product-catalogs?filter[marketplace_product_id]=...&filter[is_public]=true`)
  with a Subscribe action (`POST /s3/accounts/{id}/do/subscribe-server-package`).
  Re-subscribing on the same server **replaces** the existing package —
  there's no separate cancel step. See spec §5.1–5.2.
- **"My Storage Plan(s)" page**, under Usage & Billing: lists every server
  the account has an active package on, with its flat fee and included
  allowance. See spec §5.3 — **read the two flagged gaps below before
  building the "estimated overage in dollars" part of this screen.**
- **Admin: "Packages" tab on Server Detail:** CRUD for that server's public
  package templates (name, price, included storage, overage rate). See spec
  §6, including a warning about not exposing private per-customer clones as
  editable rows.
- **Accounts perspective:** two new fields, `included_egress_bytes_mo` and
  `egress_overage_bytes` — egress billing is account-wide and blended, not
  per-server (unlike storage). See spec §7.

## Two backend gaps worth knowing about before you build the overage displays

1. **No API returns "how much is this account storing on this specific
   server" today.** It's computed at invoice time by summing bucket sizes
   server-side, but there's no read endpoint for it. Showing a live overage
   estimate on the "My Storage Plan(s)" page means the frontend has to fetch
   this account's buckets filtered by server and sum `size_bytes` itself —
   duplicating backend math. Flagged to backend as a follow-up; not blocking,
   just don't be surprised there's no single field to read for this.

2. **The egress overage rate is a platform-wide config value with no API
   exposing it.** The overage in bytes (`egress_overage_bytes`) is already
   returned, but there's nowhere to read what that costs per GB. Show the
   overage in GB only until this is exposed — don't guess or hardcode a rate
   in the frontend.

## Why this matters enough to prioritize

Without any of this, "1TB for $20" and "pay as you go" are pricing ideas with
no way for a customer to actually pick one, and no invoice line ever reflects
what they're using. The Servers list + Package Picker + Subscribe flow (in
that order) are the minimum needed for a customer to end up on a real,
billed plan; the Admin Packages tab is what lets anyone create a package
worth subscribing to in the first place, so in practice it needs to ship
alongside the customer-facing picker, not strictly after it.
