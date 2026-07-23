# Storage Packages & Billing — UI Specification

**Purpose:** Companion to `ui-specification.md` (the base S3 module UI spec) — read
that one first for shared conventions (API base URL, error handling, status pill
colors, byte/date formatting). This document covers everything needed to build the
packaging/pricing screens: browsing a server's packages, subscribing/changing a
package, the "My Storage Plan" view, and the admin package-management screens.

Backend reference: `nextdeveloper/s3` — `AccountsService::subscribeToServerPackage()`
and `AccountsService::getActivePackageForServer()`, `ServersService::create()`, and
the two Accounting classes `App\Accounting\S3\S3ServerStorageOverageBilling` /
`S3EgressOverageBilling` in the main app. No dedicated S3 "subscriptions" table/
endpoint exists — packaging state lives entirely in the existing Marketplace
`product-catalogs`/`subscriptions` endpoints (see §3). As of 2026-07-23,
`subscribeToServerPackage()` no longer clones a private catalog row; it creates two
plain `Marketplace\Subscriptions` rows per (account, server) — one billing the flat
fee, one anchoring the overage line — each carrying a frozen price/allowance
snapshot in its own `subscription_data` column.

---

## 1. System Context

Storage is priced **per server**, not per account — each `s3_servers` row has
its own Marketplace Product, and that product's catalog (its "packages") can be
priced differently than another server's, matching physical hardware/hosting
cost differences. A customer picks a server when creating a bucket (existing
flow), and separately can **subscribe** their account to a package on that
server — e.g. "1TB for $20/month" — or leave it on the zero-config
**Pay-As-You-Go** package every server gets automatically.

A customer can hold an independent package on every server they use. There is
no "one plan per account" — plans are always scoped to (account, server).

**Billing model, in one sentence:** every package = a flat monthly fee +
an included storage allowance + a per-GB price for storage used beyond it.
Pay-As-You-Go is just the same shape with $0 flat fee and 0 included, so
every GB is billed at the overage rate. **Going over the included amount
never blocks the account — it only increases next month's bill.** Egress
(bandwidth) is billed separately: one blended, account-wide allowance/rate,
not per-server (see §7).

Two personas, same as the base spec:
- **Customer** — subscribes their own account to packages, sees their own plan(s) and estimated overage.
- **Platform Admin** — creates/edits the packages (catalog templates) sold on each server; sets each server's own `price_per_gb`.

---

## 2. Navigation Structure

Extends the tree in `ui-specification.md` §2:

```
S3 Module
├── Dashboard                          ← add "My Storage Plan(s)" widget, §5
├── Buckets
├── Access Keys
├── Backup Agents
├── Usage & Billing
│   ├── Bandwidth (monthly)
│   ├── Deposit Ledger (WORM billing)
│   ├── Usage Snapshots
│   └── My Storage Plan(s)             ← new, §5 — per-server package + overage estimate
├── Webhooks
└── [Admin Only]
    ├── Servers
    │   ├── List                      (now shows price_per_gb + "packages" count)
    │   └── Server Detail
    │       └── Packages               ← new tab, §6 — manage this server's catalog templates
    ├── Accounts (all customers)
    └── Audit Logs (all)
```

There is **no separate top-level nav item** for this feature on the customer
side — it lives inside the existing Servers list (to browse/subscribe, since
picking a server and picking its package are the same mental step for a
customer) and a new "My Storage Plan(s)" page under Usage & Billing (to see
what's already active and what it's costing).

---

## 3. API Endpoints

There is no dedicated S3 endpoint for "subscriptions" — this reuses the
generic Marketplace `product-catalogs`/`subscriptions` resources plus one new
S3 action. All Marketplace endpoints below are outside the `/s3/` prefix.

| Purpose | Endpoint | Notes |
|---|---|---|
| List servers (customer + admin) | `GET /s3/servers` or `GET /s3/servers-perspective` | Each server now returns `marketplace_product_id` and `my_active_package` (see §4) |
| List available packages for a server | `GET /marketplace/product-catalogs?filter[marketplace_product_id]={server's marketplace_product_id}&filter[is_public]=true` | Only **public template** catalogs. Subscribing no longer clones a row into this table — every catalog row you'll ever see here is a real sellable template, admin-managed (§6) |
| Subscribe (or change) a package | `POST /s3/accounts/{account_id}/do/subscribe-server-package` | Body: `{ "server_id": "<server uuid>", "product_catalog_id": "<template catalog uuid from the list above>" }`. Calling this again for the same account+server **replaces** the existing package — no separate "cancel" call needed (see §5.3) |
| My active subscriptions (raw) | `GET /marketplace/subscriptions?filter[iam_account_id]={mine}` | Rarely needed directly — prefer reading `my_active_package` off each server (§4), which already resolves which server each package belongs to. This raw endpoint doesn't tell you the server without an extra join. |
| Create/edit packages for a server (admin) | `GET/POST/PATCH/DELETE /marketplace/product-catalogs` | Filter by `marketplace_product_id` to scope to one server. See §6. |
| Account plan summary | `GET /s3/accounts-perspective` | Now includes `included_egress_bytes_mo` and `egress_overage_bytes` (see §7) |

---

## 4. Servers List & Detail (extends `ui-specification.md` §13)

### 4.1 New fields on the Servers list/detail

| Field | Label | Notes |
|---|---|---|
| `marketplace_product_id` | — | Not shown directly — used to fetch available packages (§3) and to build the "Manage Packages" admin link. |
| `my_active_package` | My Package | `null` if the current account hasn't subscribed on this server. Otherwise an object: `{ product_catalog_id, name, price, args }`. Render as a pill next to the server row, e.g. **"1TB Package — $20/mo"**, or **"Pay As You Go"** styled distinctly (e.g. outline/grey) since it has no flat fee. |
| `price_per_gb` | Base Price/GB | Already in the base spec — this is the *raw* per-GB rate the server's auto-created PAYG package uses; keep showing it as reference context even once packages exist. |

`my_active_package.args` contains the numbers needed to render a plan
summary without a second request:

```json
{
  "included_storage_bytes": 1099511627776,
  "overage_price_per_gb_storage": 0.02,
  "billing_model": "s3-flat-plus-overage"
}
```

Format `included_storage_bytes` as human-readable (reuse the base spec's
byte-formatting rule, §14). `overage_price_per_gb_storage` is the rate
charged for every GB stored on **this server** beyond the included amount.

### 4.2 Server row / detail actions

- **Browse Packages** (every server, every account) → opens the package
  picker (§5.1), pre-filtered to this server's `marketplace_product_id`.
- If `my_active_package` is set, show **Change Package** instead of
  "Subscribe" as the button label — same picker, same endpoint, it's a
  replace not a separate flow.

---

## 5. Customer: Browse & Subscribe to a Package

### 5.1 Package Picker

Triggered from a server row's "Browse Packages" / "Change Package" action.

**Data:** `GET /marketplace/product-catalogs?filter[marketplace_product_id]={server}&filter[is_public]=true`

**Card per package**, fields from the response:

| Field | Label | Notes |
|---|---|---|
| `name` | Package name | e.g. "Pay As You Go", "1TB Package" |
| `price` | Monthly fee | Format as currency; show "$0/mo" plainly for PAYG rather than hiding the fee row |
| `args.included_storage_bytes` | Included storage | Human-readable bytes; "0 GB included" for PAYG |
| `args.overage_price_per_gb_storage` | Overage rate | "$X per GB over the included amount, per month" |
| `sku` | — | Not shown to the customer; useful in dev tools for support debugging |

Highlight the card matching the server's current `my_active_package.product_catalog_id`, if any, with a "Current Plan" badge. (This match now works correctly against the `is_public=true` list above — `product_catalog_id` is the public template's own id, not a private clone's, since subscribing no longer clones anything; see §6.)

### 5.2 Subscribe / Change confirmation

On selecting a package:

**Endpoint:** `POST /s3/accounts/{account_id}/do/subscribe-server-package`
**Body:** `{ "server_id": "<server uuid>", "product_catalog_id": "<selected template's id>" }`

**Confirmation copy, if the server already has an active package:**
> "Switching to {new package name} replaces your current {current package
> name} on this server, effective immediately. Storage already on this
> server is billed under the new package's terms starting now — there is no
> partial-month proration for the flat fee."

(There genuinely is no proration logic on the backend — the new package's
flat fee bills in full for the current term, and overage from that point
forward uses the new package's included allowance/rate. Don't imply
proration in the copy.)

**On success:** refresh the server row's `my_active_package`; show a success
toast: "Subscribed to {package name} on {server name}."

**Error to handle specifically:** the API returns 403/`NotAllowedException`
with the message *"This package is not sold for the selected server."* if
the frontend somehow submits a package from a different server (shouldn't
happen if the picker is correctly scoped, but validate server-side error
message pass-through per the base spec's error-handling table).

### 5.3 "My Storage Plan(s)" page

A dedicated page (or Dashboard widget) listing every server the account has
an active package on, sourced by fetching `GET /s3/servers` (or
`servers-perspective`) and filtering client-side to rows where
`my_active_package` is non-null (there's no server-side "only my packaged
servers" filter today — fetch all reachable servers and filter).

**Per server, show:**

| Field | Label |
|---|---|
| Server name | Server |
| `my_active_package.name` | Package |
| `my_active_package.price` | Monthly Fee |
| `my_active_package.args.included_storage_bytes` | Included Storage |
| **Current storage on this server** | Storage Used | *(see note below — requires a separate call, not returned by this endpoint)* |
| **Estimated overage this month** | Est. Overage | *(computed client-side, see note below)* |

> **Important gap to design around:** neither `/s3/servers` nor any
> Marketplace endpoint returns "how much is this account currently storing
> on this specific server" — that's derived server-side at invoice time by
> summing `Buckets.size_bytes` grouped by `(s3_account_id, s3_server_id)`,
> logic that currently lives only inside the billing job, not behind any
> read API. To show a live estimate here, the frontend must independently
> fetch this account's buckets (`GET /s3/buckets-perspective?filter[s3_server_id]={server}`)
> and sum `size_bytes` client-side, then compute:
> `estimated_overage = max(0, sum(bucket sizes on this server) - included_storage_bytes) × overage_price_per_gb_storage`.
> This duplicates backend logic in the frontend — acceptable for a first
> pass, but flag to backend that a dedicated read endpoint (or a field
> added to the servers response) would remove this duplication and the risk
> of the two calculations drifting apart.

**Also show, once per page (not per server) — the egress side (§7):**
`included_egress_bytes_mo` / `current_month_egress_bytes` /
`egress_overage_bytes` from `GET /s3/accounts-perspective`.

> **Second gap to design around:** the egress overage *rate*
> (`s3.packaging.egress_overage_price_per_gb`) is a single platform-wide
> config value with **no API exposing it today**. The UI can compute
> `egress_overage_bytes` (backend already returns it) but cannot show what
> that translates to in dollars without this rate. Flag to backend to
> expose it (e.g. a small public S3 config-read endpoint, or add it as a
> field on `accounts-perspective`) before shipping a dollar estimate here —
> until then, show the overage in GB only, not in dollars.

---

## 6. Admin: Manage a Server's Packages

New tab on the Server Detail page (admin only), titled **Packages**.

**List data:** `GET /marketplace/product-catalogs?filter[marketplace_product_id]={this server}`
(no `is_public` filter here — admin manages both public and, in principle,
private catalog rows from this screen, but in practice **every row this
server's product will ever own is a public, sellable template** — see note
below).

**Columns:** `name`, `price`, `args.included_storage_bytes` (human-readable),
`args.overage_price_per_gb_storage`, `is_public`, `sku`.

> **Note (this changed 2026-07-23 — no more private clones):** subscribing
> used to clone the public template into a private, per-customer
> `ProductCatalogs` row (`is_public=false`), which meant this screen had to
> defensively filter those out and mark them non-editable. That's gone —
> subscribing now creates two `Marketplace\Subscriptions` rows that point
> straight at the public template and carry their own frozen price/allowance
> snapshot in `subscription_data`, so no per-customer row is ever written
> into `product-catalogs` anymore. Every row this screen shows is a real,
> editable, sellable package. Editing a template's `price`/`args` here only
> ever affects *future* subscribers, never anyone already subscribed — see
> §4.1 for how the frozen snapshot is exposed on `my_active_package`.

**Create Package form** (`POST /marketplace/product-catalogs`):

| Field | Input | Notes |
|---|---|---|
| `marketplace_product_id` | hidden | This server's product id — pre-filled, not user-editable |
| `name` | text | e.g. "1TB Package" |
| `sku` | text | Unique identifier; suggest a slug from the name |
| `price` | number | Flat monthly fee. `0` for a PAYG-style package |
| `common_currency_id` | select | Currency |
| `is_public` | toggle | Must be `true` to appear in the customer-facing picker (§5.1) — default on for new packages created here |
| `args.included_storage_bytes` | number + unit picker (GB/TB) | Convert to bytes before submit. `0` for PAYG-style |
| `args.overage_price_per_gb_storage` | number | Per-GB monthly rate charged beyond the included amount |
| `features` | repeatable text list | Display-only bullet points shown to customers (e.g. "1 TB included storage") — **do not** put structured data here, it's a flat string array, not JSON (see backend note below) |

> **Backend note worth surfacing to whoever builds this form:** `args` is a
> structured JSON object (safe for the numeric fields above); `features` is
> a *flat array of display strings* only — the two are cast completely
> differently on the backend (`args` → plain JSON, `features` → Postgres
> `text[]`, which silently discards any nested keys/values). Don't let the
> form builder put `included_storage_bytes`/pricing fields into `features`
> by mistake — they won't error, they'll just silently lose their structure.

**Every server always has one auto-created "Pay As You Go" package**
(`is_public=true`, `price=0`, `included_storage_bytes=0`,
`overage_price_per_gb_storage` = the server's own `price_per_gb` at the time
the server was registered). This row is editable like any other, but don't
let the admin delete it without a clear warning — if this row is removed
and no other public package exists, a customer would open the picker and
see nothing to pick.

---

## 7. Egress Billing (account-wide, not per-server)

Unlike storage, egress/bandwidth is **not** priced per server — a single
blended allowance and rate apply across the whole account, regardless of
which server(s) the account's buckets are on. This is a deliberate
simplification (egress isn't tracked per-server anywhere in the backend
today, and servers have no per-server egress price field).

**Fields, from `GET /s3/accounts-perspective`:**

| Field | Label | Notes |
|---|---|---|
| `current_month_egress_bytes` | Egress This Month | Already in the base spec |
| `included_egress_bytes_mo` | Included Egress | New — the account's free egress allowance for the month. `0` until the account subscribes to at least one server package (see below) |
| `egress_overage_bytes` | Egress Over Allowance | New — precomputed `max(0, current_month_egress_bytes - included_egress_bytes_mo)` |

**Business rule:** `included_egress_bytes_mo` starts at `0` and only becomes
non-zero the first time an account subscribes to *any* server package — it's
a byproduct of buying your first storage package, not something a customer
configures directly. There is no UI to set this value — don't build one.

As noted in §5.3, the dollar rate for egress overage isn't exposed via API
yet — show `egress_overage_bytes` in GB, not in dollars, until that gap is
closed.

---

## 8. Key Business Rules for the UI

1. **Going over the included allowance never blocks anything.** There is no
   "quota exceeded" error for a packaged account's storage/egress overage —
   it silently accrues to next month's bill. The existing hard-block
   behavior (`status = blocked`, red pill, per the base spec §15.5) is
   reserved for extreme abuse and for accounts with **no package at all**
   (today's free-tier default). Don't reuse the base spec's quota-exceeded
   warning copy for a packaged account's normal overage — that copy implies
   a block that isn't happening.

2. **Packages are per (account, server), never account-wide.** A customer
   can have a different package on every server they use. Never show a
   single "your plan" without naming which server it applies to.

3. **Subscribing again on the same server replaces the existing package,
   immediately, with no proration.** Frame this as "Change Package," not
   "Cancel then resubscribe" — it's one action on the backend.

4. **A customer's frozen price/allowance lives on their `Subscriptions` row,
   not on any `ProductCatalogs` row.** There's nothing "not editable" in
   `product-catalogs` to warn about anymore — every row there is a normal,
   editable public template (see §6). The thing that must never be treated
   as editable is a `Subscriptions.subscription_data` snapshot, but that's
   not exposed as an editable form anywhere in this spec to begin with.

5. **PAYG is not a special case in the data model** — it's just a package
   with `price=0` and `included_storage_bytes=0`. Style it distinctly in the
   UI (so it doesn't look like an error/empty state) but don't special-case
   it in API calls.

---

## 9. Error Handling (extends `ui-specification.md` §16)

| Condition | Message | UI action |
|---|---|---|
| Subscribing to a package that belongs to a different server | *"This package is not sold for the selected server."* | Shouldn't be reachable if the picker is correctly scoped by `marketplace_product_id` — if seen, treat as a bug, not a user-facing validation case worth designing copy around beyond passing the message through. |
| `product_catalog_id` or `server_id` missing/invalid on subscribe | Standard 422 validation error (both are `required|uuid`) | Standard field-level error display per base spec §16 |

---

## 10. Implementation Priority Order

1. **Servers list: show `my_active_package` + price_per_gb** — makes the
   existing (already customer-visible) server picker billing-aware with no
   new screens.
2. **Package Picker + Subscribe/Change (§5.1–5.2)** — the core purchase flow.
3. **My Storage Plan(s) page (§5.3)** — even without live GB estimates
   (flag the two gaps rather than blocking on them), showing which
   packages are active and their flat fees is immediately useful.
4. **Admin: Manage Packages per server (§6)** — needed before there's
   anything beyond the auto-created PAYG package to subscribe to.
5. **Egress overage display (§7)** — lowest priority; it's a smaller,
   blended number and the dollar amount can't be shown until the backend
   gap is closed anyway.
