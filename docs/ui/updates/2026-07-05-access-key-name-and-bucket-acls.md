# Update: access key `name` field, and a real bucket-ACL picker is needed

**Date:** 2026-07-05
**Backend:** `nextdeveloper/s3` v1.1.20
**Affects:** panel (frontend) — no agent-side impact, this is the general S3 module UI, not backup.agent specific

## What changed and why

Debugging backup.agent's storage access surfaced two long-standing gaps in the
**general** access-keys system — not specific to backup.agent, these affect
every access key ever created through the dashboard.

### 1. Every key's name has always come back blank

`AccessKeysService::create()` never sent a `name`/`user_id` to the storage
agent's `iam_create` command. Confirmed live: every non-admin identity in the
storage agent's IAM config had `"name": ""`, regardless of what (if anything)
was in our own database. There was no `name` column on `s3_access_keys` at
all until now.

**Fix:** added `s3_access_keys.name` (nullable text), threaded it through to
the storage agent, and it falls back to the access key string itself if left
blank — better than always-blank, but still worth prompting for in the UI so
customers actually get to label their keys.

### 2. Every key created through the dashboard has always gotten unrestricted, all-bucket access

This is the more important one. `S3AccessKeyCreateForm.vue` never sends
`bucket_acls` at all — it only sends `s3_account_id`, `role`, `expires_at`.
On the backend, `AccessKeysService::create()` passes whatever `bucket_acls`
it's given straight through (`$model->bucket_acls ?? []`), and on the storage
agent side, an **empty `bucket_acls` grants full, unscoped `Read`/`Write`/
`List`/`Tagging`/`Admin` access to every bucket the account has** (that's the
Go agent's own `buildActions()` behavior when `BucketACLs` is empty — not a
bug, just a default nobody built a UI to override).

So: **every access key created through the dashboard today can read and write
every bucket on the account, with no way to scope it down, and the UI gives no
indication this is happening.** That's the thing worth fixing with an actual
UI, not just documenting — a customer creating a key for one integration has
no way to limit its blast radius to just the one bucket that integration
needs.

## What to build

Full field-by-field spec is in `../ui-specification.md` §6.1–6.3 (already
updated) — the short version:

- **Access Key List (§6.1):** add a `name` column, and a derived "Scope"
  indicator (All buckets / N buckets) so it's visible at a glance which keys
  are broad vs scoped — this alone is a meaningful security-visibility win
  even before anyone touches the create form.
- **Create Access Key (§6.2):** add `name`, and a real bucket-ACL picker —
  radio between "All buckets" (today's invisible default, made explicit and
  named) and "Specific buckets" (repeatable bucket + permission-level rows).
  The exact payload shape backend expects:
  ```json
  "bucket_acls": [
    { "bucket_id": "my-bucket", "permission": "rw" }
  ]
  ```
  Note `bucket_id` is the bucket **name**, not its UUID, despite the field
  name — this tripped us up on the backend side too, worth flagging so
  whoever builds this doesn't repeat it.
- **Edit Access Key (§6.3, new):** only expose `name` as editable. Do **not**
  build role/ACL editing — there's no backend mechanism that pushes a changed
  ACL out to the already-created identity on the storage agent (only
  create/delete exist, no update), so a PATCH that "succeeds" would silently
  diverge from reality. Revoke + recreate is the only correct way to change a
  key's permissions, and the UI should make that the obvious path rather than
  offering an edit that doesn't do what it looks like it does.

## Why this matters enough to prioritize

This isn't a cosmetic gap — every access key a customer has ever created
through this dashboard has unrestricted access to every bucket on their
account, and nothing in the current UI tells them that. The List view's Scope
column and the Create form's explicit All/Specific choice are the two pieces
that actually change that, in that order of priority if this needs to be
split across more than one pass.
