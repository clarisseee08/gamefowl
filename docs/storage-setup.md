# Photo Storage Setup

Broodcock photos are the only user-uploaded files in GFMS. Where they are
written is decided at runtime by one setting:

```env
GFMS_PHOTO_DISK=public     # default - local folder, zero cloud configuration
GFMS_PHOTO_DISK=supabase   # private Supabase Storage bucket
```

Nothing in the application hard-codes a disk name. `config('gfms.photo_disk')`
picks the disk when a photo is stored, and the chosen disk is written to the
photo's own `disk` column. Every read - the gallery, the photo controller,
`BroodcockPhoto::url()` - goes back to **that row's** disk, not to the current
setting.

That has one consequence worth stating plainly:

> Switching the setting does **not** move existing photos. It changes where the
> *next* photo goes. Old rows keep pointing at the old disk and keep working, as
> long as those files are still there. The two can coexist indefinitely.

---

## 1. The default: local `public` disk

This is what a fresh checkout runs on, and it needs no credentials at all.

```bash
php artisan storage:link     # once - links public/storage -> storage/app/public
```

Files land in `storage/app/public/broodcocks/{broodcock_id}/{uuid}.{ext}`.

Photos are still served through `GET /photos/{photo}`
(`BroodcockPhotoController@show`), not through the symlink, so the Policy is
checked on every image request on this disk too. The symlink only matters for
`BroodcockPhoto::url()`, which the gallery does not use.

---

## 2. Switching to Supabase Storage

### 2.1 Credentials

Add to `.env` (never to a committed file):

```env
GFMS_PHOTO_DISK=supabase

SUPABASE_S3_ACCESS_KEY_ID=...
SUPABASE_S3_SECRET_ACCESS_KEY=...
SUPABASE_S3_ENDPOINT=https://uieekjpnzxyrvwmfvlew.storage.supabase.co/storage/v1/s3
SUPABASE_S3_REGION=ap-northeast-1
SUPABASE_S3_BUCKET=gfms-laravel
```

Then `php artisan config:clear`.

Generate the key pair in the Supabase dashboard under
**Project Settings → Storage → S3 access keys**. These are *not* the project's
anon or service-role API keys.

### 2.2 Settings that are already correct - do not change them

`config/filesystems.php` is set up and needs no edits. Three of its values are
load-bearing:

| Setting | Why it must stay |
|---|---|
| `use_path_style_endpoint => true` | Supabase addresses objects as `<endpoint>/<bucket>/<key>`. Virtual-host style would need per-bucket DNS that does not exist, and fails with a name-resolution error rather than an auth error. |
| `region => ap-northeast-1` | Region is not routing here - it is baked into the SigV4 string-to-sign. A wrong region gives `SignatureDoesNotMatch`, which looks like a bad password but is not. |
| `throw => true` | A failed upload raises instead of returning `false`. `StorePhoto` relies on this: an exception means no database row is written, so the farm never gets a record pointing at a photo that was never stored. |

No `endpoint_provider` workaround is needed. That workaround exists for
`aws/aws-sdk-php` below 3.369.0; this project is on 3.392.3.

### 2.3 Leave the bucket private

The `gfms-laravel` bucket is private and must stay private. Making it public
would not "fix" anything - it would remove the only thing that keeps a customer
from reading another farm's photos by guessing a URL.

### 2.4 Do NOT point Livewire's temporary upload disk at Supabase

```env
# Leave this unset.
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=
```

Livewire stages an upload on a temporary disk before the application moves it to
its final home. That temporary disk defaults to `FILESYSTEM_DISK` (`local`),
which is correct and should be left alone. If it is pointed at an S3-driver disk,
Livewire switches to direct-to-S3 presigned uploads and **throws
`S3DoesntSupportMultipleFileUploads`** - the "choose several photos at once"
flow, which is the whole point of the upload screen for farm staff, stops
working. The final destination is still Supabase either way; only the staging
area is local.

---

## 3. What actually changes when the switch is flipped

| | `public` | `supabase` |
|---|---|---|
| Where new files go | `storage/app/public/broodcocks/{id}/` | bucket key `broodcocks/{id}/` |
| Credentials needed | none | S3 access key pair |
| Read cost | local filesystem read | HTTPS round trip to Tokyo per file |
| Failed write | returns `false` silently (`throw => false`) | raises (`throw => true`) |
| `BroodcockPhoto::url()` | plain `/storage/...` URL | SigV4 presigned URL, 30 min |
| How the gallery loads images | `GET /photos/{photo}` | `GET /photos/{photo}` - unchanged |
| Survives a redeploy / new container | no, unless the volume persists | yes |

The application code path is identical. The two things to plan for are **latency**
and **failure mode**.

Latency is why `BroodcockPhotoController` derives `Content-Type` from the stored
file extension instead of asking the disk for it: `Storage::mimeType()` is an
extra `HeadObject` request, and a gallery of twelve thumbnails would pay for it
twelve times. It is also why the response carries
`Cache-Control: private, max-age=3600` - the browser reuses the image instead of
re-fetching it from Tokyo on every page view. `private` is deliberate: the
response is authorized per user, so no shared proxy or CDN may keep a copy.

---

## 4. Verifying the switch

### 4.1 Prove the credentials work, without touching the app

```bash
php artisan tinker
```

```php
Storage::disk('supabase')->put('healthcheck.txt', 'hello');
Storage::disk('supabase')->get('healthcheck.txt');        // 'hello'
Storage::disk('supabase')->delete('healthcheck.txt');
```

If this throws:

| Error | Cause |
|---|---|
| `SignatureDoesNotMatch` | region or secret key wrong |
| `InvalidAccessKeyId` | key id wrong, or an anon/service key was used instead of an S3 key |
| `NoSuchBucket` | bucket name wrong, or the bucket was created in another project |
| cURL name-resolution failure | endpoint wrong, or `use_path_style_endpoint` was turned off |

### 4.2 Prove the app end-to-end

1. Confirm the setting is live: `php artisan tinker --execute="echo config('gfms.photo_disk');"` → `supabase`.
2. Upload a photo from a broodcock's page as an owner or staff account.
3. Check the new row: its `disk` column should read `supabase` and its `path`
   should be `broodcocks/{id}/{uuid}.{ext}`.
4. Confirm the object exists in the Supabase dashboard under that exact key.
5. Reload the bird's page - the thumbnail must render (it is being streamed
   through `/photos/{id}`, so this proves read access as well).
6. Sign in as a **customer** and open `/photos/{id}` directly: it must return the
   image (customers may view).
7. Sign out entirely and open the same URL: it must redirect to the login page,
   **not** return the image.
8. Delete the photo from the gallery and confirm the object disappears from the
   bucket.

### 4.3 Rolling back

Set `GFMS_PHOTO_DISK=public` and `php artisan config:clear`. Photos already
written to Supabase keep loading from Supabase, because each row remembers its
own disk. Nothing needs to be migrated to roll back.

---

## 5. Tests never touch the bucket

Every test in `tests/Feature/Photos/` calls `Storage::fake()` on the configured
disk in `setUp()`. The suite therefore never authenticates to Supabase, never
uploads, and cannot leave stray objects behind - including on CI, where the
credentials are not present at all.

If a photo test ever starts failing with an AWS error, something has bypassed
`Storage::fake()`; that is a bug in the test, not a reason to add credentials to
the test environment.
