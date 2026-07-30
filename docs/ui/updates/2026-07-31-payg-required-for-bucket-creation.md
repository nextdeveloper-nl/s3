# Update: bucket creation now requires (and auto-arranges) a billing subscription

**Date:** 2026-07-31
**Backend:** `nextdeveloper/s3` v1.1.40
**Affects:** panel (frontend) — "Create Bucket" flow, wherever `POST
/s3/buckets` is called

## What changed and why

Previously, an account could create buckets and accrue billable
storage/egress usage with **no** Marketplace subscription at all — overage
billing only bills accounts with an active subscription, so this usage
silently went unbilled. See
[`how-to-show-active-package.md`](how-to-show-active-package.md) for how a
package/subscription is represented.

**Fix:** `POST /s3/buckets` now guarantees every new bucket has a billing
anchor before it's created:

- If the account has no active subscription on that server yet, it's
  **silently auto-subscribed to Pay-As-You-Go** (the server's `$0`-flat-fee,
  billed-per-GB catalog entry) — no UI action needed, this is transparent.
- If the **server itself** has no sellable package at all (no Marketplace
  product attached, or no PAYG catalog entry configured for it), bucket
  creation is **rejected outright**. This should be rare/internal — it means
  the server was never packaged for sale (see `ServersService::ensurePackaging()`)
  — but the UI needs to handle it rather than show a raw/blank error.

## What the UI needs to handle

`POST /s3/buckets` can now fail with a `422` when the target server isn't
sellable. Response shape (standard framework error envelope):

```json
{
  "status": 422,
  "message": "You are not allowed to access this resource. That is why we denied this request.",
  "helper": "You are not allowed to access this resource. That is why we denied this request.Error message is: This server has no Pay-As-You-Go package configured — buckets cannot be created on it until one exists.",
  "code": 0
}
```

Two things worth knowing before you wire up error display:

- **`message` is a generic, unhelpful boilerplate string** — always the
  same text regardless of what actually went wrong. The real, specific
  reason is in **`helper`**, but note `helper` is the generic boilerplate
  **concatenated in front of** the specific reason with no separating space
  before `"Error message is:"` — this is a pre-existing platform-wide
  quirk of how `NotAllowedException` renders, not specific to this
  endpoint. Don't string-match the whole `helper` value; if you need to
  detect this specific case client-side, match on a substring like `"Pay-
  As-You-Go"` / `"Marketplace product attached"` instead, or just display
  `helper` as-is (ugly but functional) since this should be a rare,
  effectively-internal-error case rather than a normal validation failure
  a customer would routinely hit.
- This is a **different, much rarer case** than a normal 4xx validation
  error (bad bucket name, quota exceeded, etc.) — those already existed and
  are unaffected. This new failure mode specifically means "this server was
  never set up to be sold," which is an operational/admin problem, not
  something the customer did wrong. Consider surfacing it as "This storage
  location isn't currently available — please contact support" rather than
  a generic form-validation-style error.

No other request/response shape changed — the normal, common-case create
flow (server already has PAYG or the account already has a plan) is
unaffected and returns the bucket as before.

## Backend note (context only, not needed to build the UI)

The two failure messages are literal strings from
`AccountsService::ensurePaygSubscriptionOrFail()`:
`"This server has no Marketplace product attached — buckets cannot be
created on it until it is packaged for sale."` and `"This server has no
Pay-As-You-Go package configured — buckets cannot be created on it until
one exists."` — useful if you do want to branch UI copy on which of the two
happened.
