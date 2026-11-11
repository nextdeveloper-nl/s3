# How to Show Which Package an Account Is Subscribed To

**Question this answers:** on a given S3 server, how does the panel know
whether — and to what — the current account has subscribed?

**One-line answer:** read the `my_active_package` field that's already on
every server object returned by `GET /s3/servers` (or
`GET /s3/servers-perspective`). No separate subscription lookup is needed.

---

## 1. The field

```
GET /s3/servers
```

Each server in the response includes:

```json
{
  "id": "9e09aeda-20eb-4d70-a393-34f2ccc474ee",
  "hostname": "dc.s3.plusclouds.com",
  "price_per_gb": 0.023,
  "my_active_package": {
    "subscription_id": "b6f1...",
    "product_catalog_id": "1c2d...",
    "name": "1TB Package",
    "price": 18.84,
    "args": {
      "included_storage_bytes": 1099511627776,
      "overage_price_per_gb_storage": 0.023,
      "billing_model": "s3-flat-plus-overage"
    }
  }
}
```

| Field | Meaning |
|---|---|
| `my_active_package` | `null` if the current account has never subscribed on **this** server — including never explicitly picking Pay-As-You-Go. Otherwise the object below. |
| `subscription_id` | The account's own `Marketplace\Subscriptions` row id. Not generally needed by the UI; useful for support/debug. |
| `product_catalog_id` | The **public template's** id — use this to match against the package picker list (see §3). |
| `name` | e.g. `"1TB Package"`, or `"Pay As You Go"`. |
| `price` | Flat monthly fee. `0` for Pay-As-You-Go — show "$0/mo" rather than hiding the row. |
| `args.included_storage_bytes` | Included storage allowance, in bytes. `0` for PAYG. |
| `args.overage_price_per_gb_storage` | Per-GB rate charged for storage beyond the included amount. |

This is a **per-server** field — an account can be on a different package
(or no package) on every server it uses. There is no single account-wide
"my plan."

---

## 2. Rendering it

- **Subscribed (paid tier):** pill next to the server row, e.g.
  **"1TB Package — $18.84/mo"**.
- **Subscribed (PAYG):** same pill, styled distinctly (outline/grey) since
  there's no flat fee — e.g. **"Pay As You Go"**.
- **Not subscribed (`my_active_package` is `null`):** show a "Browse
  Packages" call to action instead of a plan pill.
- Format `included_storage_bytes` with the base spec's byte-formatting
  rule; format `overage_price_per_gb_storage` as currency, e.g.
  `"$0.023 / GB over included"`.

---

## 3. Matching it against the package picker

When browsing available packages for a server:

```
GET /marketplace/product-catalogs?filter[marketplace_product_id]={server}&filter[is_public]=true
```

Highlight the card whose `id` equals the server's
`my_active_package.product_catalog_id` with a "Current Plan" badge. This
comparison works directly — `product_catalog_id` is a real public
template id, not a private per-customer copy, so it always appears in this
list when there's an active subscription.

---

## 4. Showing every package an account holds, across all servers

There's no single endpoint for this. Fetch `GET /s3/servers` (or
`-perspective`), then filter client-side to the servers where
`my_active_package` is non-null. This is what the "My Storage Plan(s))"
page (Usage & Billing) is built from — see
[`packaging-billing-ui-specification.md`](packaging-billing-ui-specification.md)
§5.3 for the full page spec, including the known gap around live storage
usage per server.

---

## Backend note (context only, not needed to build the UI)

`my_active_package` is resolved by
`AccountsService::getActivePackageForServer()` off the account's `'fee'`
role `Marketplace\Subscriptions` row for that server — a frozen
price/allowance snapshot taken at subscribe time, not a live catalog
lookup. That's why it always reflects what the customer is actually being
billed, even if the public template's price has changed since they
subscribed. See
[`packaging-billing-ui-specification.md`](packaging-billing-ui-specification.md)
for the full subscribe/change/admin-package flow.
