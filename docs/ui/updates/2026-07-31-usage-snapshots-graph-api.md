# Update: a real endpoint now exists for graphing S3 usage over time

**Date:** 2026-07-31
**Backend:** `nextdeveloper/s3` v1.1.41
**Affects:** panel (frontend) — `S3UsageSnapshots.vue` (currently a flat table), any
new "Usage" chart on the S3 dashboard/account page

## What changed and why

`s3_usage_snapshots` has always existed and been populated every 15 minutes
(one row per account: `storage_bytes`, `object_count`), but it was never
actually queryable for a chart:

- The account filter was broken — `filter[s3AccountId]=...` (or the
  snake_case `s3_account_id`) would fatal or no-op depending on which alias
  you hit.
- The only endpoint available was the raw paginated list (`GET
  /s3/usage-snapshots`), which for a 30-day window returns ~2,880
  15-minute-granularity rows per account — far too dense to hand straight to
  a chart library.

This is why `S3UsageSnapshots.vue` currently renders a plain, unsorted table
instead of a graph, despite `vue3-apexcharts` already being a project
dependency.

**Fix:** the account filter bugs are fixed, and a new endpoint returns the
data already bucketed by day — ready to plot directly.

## The new endpoint

```
POST /s3/usage-snapshots/series
```

It's a `POST`, not a `GET` — same reason as `access-keys/reveal` and a few
other custom S3 endpoints: a `GET` here would collide with the generated
`GET /s3/usage-snapshots/{id}` route and 404 instead of running.

**Request body / params** (all optional):

| Param | Meaning |
|---|---|
| `s3_account_id` | UUID or internal id of the S3 account to graph. **Omit this to default to the caller's own account** — that's the normal case for a customer-facing dashboard. |
| `from` | Inclusive lower bound on `snapshot_at`. Any string `strtotime`/Carbon can parse, e.g. `2026-07-01`. |
| `to` | Inclusive upper bound, same format. |

If you pass `s3_account_id` for an account the caller isn't authorized to
see, the endpoint returns a 404-style "not found" error, not that account's
data — don't build any client-side guard around this, the backend already
refuses it.

**Response:**

```json
{
  "data": [
    { "day": "2026-07-05 00:00:00+00", "storage_bytes": 13704.0, "object_count": 8.0 },
    { "day": "2026-07-06 00:00:00+00", "storage_bytes": 14210.0, "object_count": 9.0 }
  ]
}
```

- One point **per calendar day** that has at least one snapshot — days with
  no data are simply absent from the array, not zero-filled. Pad/interpolate
  client-side if the chart needs a continuous axis.
- `day` is a raw Postgres `timestamptz` string (`YYYY-MM-DD HH:MM:SS+00`),
  **not** a clean `YYYY-MM-DD` date. Parse it with a real date library
  (`dayjs`/`date-fns`/native `Date`) rather than string-slicing it — the
  offset suffix will break naive parsing.
- `storage_bytes` and `object_count` are the **average** of every 15-minute
  snapshot within that day, always returned as floats (even though
  `object_count` is conceptually an integer — round for display).

## Rendering it

- Use `vue3-apexcharts` (already a dependency) — a simple area or line chart
  keyed by `day`, one series for `storage_bytes` (formatted through the
  existing byte-formatting helper) and optionally a second for
  `object_count`.
- Default range: last 30 days is a reasonable starting point — pass
  `from=<30 days ago>` rather than fetching the full history, since nothing
  prunes `s3_usage_snapshots` and older accounts may have months of data.
- This is **average daily usage**, not live current usage — don't use this
  endpoint for a "current storage used" stat tile. For that, the account's
  live totals are already available elsewhere (the same `storage_bytes_used`
  the billing classes read), no need to hit this endpoint for a single
  current-value display.

## Backend note (context only, not needed to build the UI)

`UsageSnapshotsService::getDailySeriesForAccount()` groups with Postgres
`date_trunc('day', snapshot_at)` and `avg()` server-side — no aggregation
happens in PHP, so this scales fine regardless of how many 15-minute rows
exist. See `AccountsService::ensurePaygSubscriptionOrFail()` and
[`2026-07-31-payg-required-for-bucket-creation.md`](2026-07-31-payg-required-for-bucket-creation.md)
for the related billing-enforcement change from the same release.
