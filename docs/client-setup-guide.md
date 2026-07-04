# S3 Service — Client Setup Guide

**Version:** 1.0
**Status:** Draft
**Audience:** Support staff / customers connecting a desktop tool to the S3 service
**Companion documents:** `seaweedfs-s3-service-standard.md`, `s3-market-and-support-analysis.md`

---

## 1. What you need before you start

Every client below needs the same four values. These come from the customer's S3 dashboard (Access Keys and Buckets screens), not from this guide:

| Value | Where to find it | Notes |
|---|---|---|
| **Endpoint URL** | S3 dashboard / provisioning email | e.g. `https://s3.customer-domain.example` — always `https://`, never `http://` |
| **Access Key ID** | Access Keys screen | Format `AKIA_<slug>_<random>` |
| **Secret Access Key** | Shown **once**, at key creation | **Copy it immediately.** It cannot be viewed again — losing it means requesting a new key, which breaks every client configured with the old one. |
| **Bucket name** | Buckets screen | Must start with the account's slug, e.g. `customer-a-reports`. A bucket named outside this pattern will fail to create or open with a permission error that looks like a credentials problem — it isn't. |

**Critical setting every tool below needs:** this service requires **path-style addressing** (`https://endpoint/bucket-name/...`), not the AWS-default virtual-hosted style (`https://bucket-name.endpoint/...`). Wherever a tool exposes a "path style" / "force path style" / "legacy path" option, it must be turned **on**. Skipping this is the single most common cause of a working-looking config that returns 403 or 404.

---

## 2. Client comparison

| Tool | Platforms | Cost | Style | Best for |
|---|---|---|---|---|
| **Cyberduck** | Windows, Mac (Linux: CLI only) | Free, open-source | Separate browser window, drag-and-drop | Default recommendation — easiest for non-technical users |
| **S3 Browser** | Windows only | Free | Separate browser window | Windows users who want a tool built only for S3 |
| **Mountain Duck** | Windows, Mac | Paid | Mounts bucket as a drive letter / Finder volume | Users who want the bucket to behave like a local folder |
| **WinSCP** | Windows only | Free | Separate browser window | Users who already know WinSCP from SFTP work |
| **s3fs-fuse** | Linux | Free, open-source | Mounts bucket as a folder (via terminal setup) | Linux desktop — closest thing to a GUI once configured |

---

## 3. Recommended setup per OS

### Windows → Cyberduck (recommended)

1. Download and install Cyberduck from the official site.
2. **Open Connection** → choose **Amazon S3** as the protocol.
3. Fill in:
   - **Server:** the endpoint host (e.g. `s3.customer-domain.example`, without `https://`)
   - **Port:** `443`, SSL enabled
   - **Access Key ID:** from the dashboard
   - **Secret Access Key:** from the dashboard
4. Open **More Options** and enable **Path Style** / **Use path style addressing** — this is required, see Section 1.
5. Connect, then navigate into the bucket (it must match the account's slug prefix).
6. **Verify:** you should see the bucket's existing objects listed, or an empty folder for a new bucket. Try uploading a small test file and confirm it appears.

**Alternative: S3 Browser**

1. Install S3 Browser and choose **Add new account** → **Add S3 Compatible Storage**.
2. Fill in the same four values as above (Endpoint, Access Key, Secret Key), and set **REST endpoint** to the account's endpoint host.
3. In account settings, ensure the endpoint is *not* used with virtual-hosted addressing — S3 Browser's "S3 compatible storage" mode already defaults to path-style, which matches this service.
4. Verify by listing bucket contents and uploading a test file.

### Mac → Cyberduck (recommended)

Same steps as Windows above — Cyberduck's UI is identical across platforms. Install via the Mac app or Homebrew (`brew install --cask cyberduck`), then follow steps 2–6 above.

**Alternative: Mountain Duck**

1. Install Mountain Duck.
2. **Add a new bookmark** with type **Amazon S3**, same four values as above.
3. Enable **Path Style** in the bookmark's advanced settings.
4. Once connected, the bucket appears as a mounted volume in Finder — files can be dragged in/out like any local folder.
5. Verify by opening the mounted volume in Finder and confirming the bucket's contents are visible.

### Linux → s3fs-fuse + Nautilus

1. Install s3fs-fuse:
   ```bash
   sudo apt install s3fs
   ```
2. Create a credentials file with the access key and secret key:
   ```bash
   echo ACCESS_KEY:SECRET_KEY > ~/.passwd-s3fs
   chmod 600 ~/.passwd-s3fs
   ```
3. Create a mount point and mount the bucket, pointing `-o url` at the account's endpoint and passing `-o use_path_request_style` (this is the path-style requirement from Section 1 — s3fs-fuse defaults to virtual-hosted style, so this flag must be included or the mount will fail):
   ```bash
   mkdir -p ~/s3-bucket-name
   s3fs bucket-name ~/s3-bucket-name \
     -o passwd_file=$HOME/.passwd-s3fs \
     -o url=https://s3.customer-domain.example \
     -o use_path_request_style
   ```
   > **Note for fish shell users:** use `$HOME` instead of `~` inside `-o passwd_file=...`. Fish only expands `~` when it's the very first character of an argument — embedded after `passwd_file=` it's left as a literal `~`, causing `s3fs: specified passwd_file is not readable` even when the file exists with correct permissions. `$HOME` expands correctly in any position.
4. Open `~/s3-bucket-name` in Nautilus (Files) — it now behaves like any other folder.
5. **Verify:** the bucket's existing files should be listed; try copying a test file in via Nautilus and confirm it uploads.
6. To make the mount persistent across reboots, add an entry to `/etc/fstab` (support staff can provide the exact line on request — this is an extra step beyond first-time setup and not required just to confirm the connection works).

---

## 4. Common problems

| Symptom | Cause | Fix |
|---|---|---|
| "Access Denied" / 403 immediately after connecting | Path-style addressing not enabled | Enable "path style" / `use_path_request_style` per Section 1 |
| Can't create or see a bucket, error looks like a credentials issue | Bucket name doesn't start with the account's slug | Use a bucket name prefixed with the account slug, e.g. `customer-a-*` |
| Connection refused / SSL error | Using `http://` or the wrong port | Always use `https://` on port 443 |
| "Invalid Access Key" after previously working | Secret key was lost and rotated | Reconfigure every client with the new Access Key ID and Secret Access Key — the old ones stop working immediately on rotation |
| Large file uploads time out | Very large single-PUT upload without multipart | Use a client/mode that supports multipart upload (Cyberduck, S3 Browser, and s3fs-fuse all do this automatically) |
| s3fs-fuse mount command returns no error, files copied via Nautilus never show up in the bucket (dashboard/other tools) | Bucket name in the `s3fs` command doesn't match an existing bucket the account owns — mount and file copy silently succeed against what is effectively a dead reference, so the file only ever lands on local disk | Double-check the exact bucket name on the Buckets screen and use that verbatim in the `s3fs` command. Confirm the mount is really active with `mount \| grep s3fs` before trusting any file copy — an entry should appear there for the mount point |

---

*End of Document*
