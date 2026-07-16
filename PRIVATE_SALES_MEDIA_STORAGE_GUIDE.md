# Private Sales Media Storage Guide

## Scope

This guide covers the three private files attached to a sale:

- `owner_id_image`
- `buyer_id_image`
- `contract_image`

These files can contain identity and contract information. They must not be stored under `public/` or exposed through permanent public URLs.

## Current backend behavior

The Laravel backend already implements the private-storage flow for Sales:

1. `POST /api/sales` and `PUT /api/sales/{sale}` accept the three optional files as `multipart/form-data`.
2. `SaleService` saves each uploaded file to the `local` disk under the `sales/` directory.
3. The `local` disk root is `storage/app/private`, so the physical location is:

   ```text
   storage/app/private/sales/<random-file-name>.<extension>
   ```

4. The database stores only the relative storage path, for example:

   ```text
   sales/8ea641cc4e0c433688f07177619e26d0.jpg
   ```

5. `SaleResource` replaces the stored path with a temporary signed URL in API responses. The URL expires after 30 minutes.
6. Deleting a sale through the sale-order deletion flow also deletes its three private media files.

The relevant backend files are:

- `config/filesystems.php`
- `app/Services/SaleService.php`
- `app/Http/Resources/SaleResource.php`
- `app/Http/Requests/StoreSaleRequest.php`
- `app/Http/Requests/UpdateSaleRequest.php`
- `tests/Feature/SaleApiTest.php`

## Why this flow is needed

The old public-storage flow placed Sales files under `public/data/uploads/sales`. Anyone who knew or guessed a file URL could request it directly, and the URL did not expire.

The private flow gives the following behavior:

- Files are outside the web server's public document root.
- The API returns a time-limited URL instead of a raw filename.
- Changing the file path, expiration, or signature invalidates the URL.
- An expired or invalid signed URL returns HTTP `403`.
- Database records remain independent of the API host name and deployment domain.

A signed URL is a bearer link: anyone who receives it can use it until it expires. The frontend must therefore avoid logging, sharing, or permanently caching these URLs.

## API contract

All Sales API endpoints below require a Sanctum bearer token:

```http
Authorization: Bearer <token>
Accept: application/json
```

The authenticated user must also have a supported `permetions_level`. The current Sales controller accepts levels `1`, `2`, `3`, and `4`.

### Upload when creating a sale

```http
POST /api/sales
Content-Type: multipart/form-data
```

Send the normal required Sale fields and optionally attach:

| Field | Type | Required | Maximum size |
|---|---|---:|---:|
| `owner_id_image` | file | No | 50 MB |
| `buyer_id_image` | file | No | 50 MB |
| `contract_image` | file | No | 50 MB |

The current validators accept general files; they do not restrict these fields to image MIME types.

Browser example:

```js
const formData = new FormData();

formData.append('car_id', String(values.carId));
formData.append('car_name', values.carName);
formData.append('price', String(values.price));
formData.append('date', values.date);
formData.append('user_id', String(values.userId));

if (values.ownerIdFile) {
    formData.append('owner_id_image', values.ownerIdFile);
}

if (values.buyerIdFile) {
    formData.append('buyer_id_image', values.buyerIdFile);
}

if (values.contractFile) {
    formData.append('contract_image', values.contractFile);
}

const response = await fetch(`${apiBaseUrl}/api/sales`, {
    method: 'POST',
    headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${accessToken}`,
    },
    body: formData,
});
```

Do not manually set the `Content-Type` header in this browser example. The browser adds the required multipart boundary.

### Replace media when updating a sale

```http
PUT /api/sales/{sale}
Content-Type: multipart/form-data
```

The update request currently requires the main Sale fields again. If a media field contains a new uploaded file, the backend stores the new file and updates the database path. If the field is omitted, the existing path is preserved.

The current update implementation does not delete the previously stored file when it is replaced. Add old-file cleanup if replacement orphan files need to be prevented.

Some clients and servers have unreliable multipart parsing for a real HTTP `PUT`. If that occurs in the deployed stack, send a multipart `POST` with `_method=PUT`; Laravel will route it as the existing `PUT /api/sales/{sale}` operation.

### Read media links

The following existing endpoints serialize the media fields as temporary URLs:

- `GET /api/sales?status={status}`
- `GET /api/sales/{sale}`
- `POST /api/sales`
- `PUT /api/sales/{sale}`
- Sales completion, approval, and installment-contract mutation responses that use `SaleResource`

Example response fragment:

```json
{
  "status": "success",
  "message": "...",
  "data": {
    "id": 123,
    "owner_id_image": "https://api.example.com/storage/sales%2F8ea641cc4e0c433688f07177619e26d0.jpg?expiration=...&signature=...",
    "buyer_id_image": null,
    "contract_image": "https://api.example.com/storage/sales%2F1dfed7596f164e8ca38b78b55ea823dd.pdf?expiration=...&signature=..."
  }
}
```

The exact encoded path and query-string order are framework details. The frontend must treat each returned value as an opaque URL and use it unchanged.

The API returns `null` when the database field is empty or when the referenced private file does not exist.

### Requesting the signed file

The returned URL points to Laravel's `GET /storage/{path}` route. This request does not require the Sanctum bearer token; the temporary signature authorizes access to that specific path until expiration.

The frontend should:

- Use the complete URL returned by the API.
- Never rebuild the URL from a filename or database path.
- Never prepend `/data/uploads/sales`, `/storage`, or the API base URL to an already absolute response value.
- Refresh the Sale record to obtain a new URL after a `403` caused by expiration.
- Keep `null` media fields as unavailable media in the UI.
- Avoid storing signed URLs in long-lived application state, local storage, analytics events, or logs.

For an `<img>` or `<a>` element, assign the returned URL directly:

```js
imageElement.src = sale.owner_id_image;
downloadLink.href = sale.contract_image;
```

If the frontend downloads the file with `fetch()` from a different origin, the server's CORS policy must allow the frontend origin for the `/storage/*` response as well as the API responses. This repository currently has no published `config/cors.php`, so that policy must be verified in the deployed environment before using cross-origin JavaScript downloads.

## Server deployment changes

### Required Laravel configuration

The deployed application must retain this `local` disk configuration:

```php
'local' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'serve' => true,
    'visibility' => 'private',
    'throw' => false,
    'report' => false,
],
```

`serve => true` registers Laravel's signed `storage.local` route. Verify it after deployment:

```bash
php artisan optimize:clear
php artisan route:list --name=storage
php artisan config:show filesystems.disks.local
```

The route list should contain `GET|HEAD storage/{path}` named `storage.local`. A `PUT` route named `storage.local.upload` is also registered by Laravel, but the Sales flow does not use direct-to-storage uploads.

### Environment

Set `APP_URL` to the public HTTPS origin of the Laravel API before caching configuration:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
FILESYSTEM_DISK=local
```

Then rebuild cached configuration using the project's normal deployment flow. A wrong `APP_URL`, reverse-proxy scheme, or host can produce signed links that point to the wrong origin or fail signature validation.

If TLS terminates at a reverse proxy, Laravel must receive the forwarded host and protocol correctly. Verify this by reading a freshly returned media URL from `GET /api/sales/{sale}` on the deployed server.

### Web server and permissions

- The web server document root must be the Laravel `public/` directory, not the repository root.
- Do not add a web-server alias that exposes `storage/app/private`.
- `php artisan storage:link` is not required for Sales private media. That link targets `storage/app/public`, not `storage/app/private`.
- The PHP/web-server user needs read and write access to `storage/app/private/sales`.
- Include `storage/app/private` in the server backup plan. Deployments must not replace or clear it.
- On multiple application servers, local private storage is not shared. Use shared persistent storage or move the disk to an object-storage driver before horizontally scaling this flow.

### Legacy file migration

Existing records may contain only a filename from the old public directory. `SaleResource` supports those values by treating them as `sales/<filename>`, but the physical file must exist in the new private directory.

Before switching production traffic:

1. Back up the database and `public/data/uploads/sales`.
2. Create `storage/app/private/sales` with the correct owner and permissions.
3. Copy, do not immediately move, the old files into the new private directory while preserving filenames.
4. Confirm that existing Sale records return working signed URLs.
5. Confirm that newly uploaded files appear only under `storage/app/private/sales`.
6. Remove the old public copies only after validation and a rollback window.

If a database value already starts with `sales/`, do not prefix it again. If legacy records contain full URLs or deeper public paths, normalize those values separately before enabling the private-only flow.

## Database changes

No schema migration is required for the current implementation.

The existing `sales` table already has text columns for:

- `owner_id_image`
- `buyer_id_image`
- `contract_image`

These columns should continue storing relative disk paths, not signed URLs. Signed URLs contain an expiration and must be generated when the API resource is serialized.

Recommended stored value:

```text
sales/<generated-file-name>.<extension>
```

Do not store:

- `https://...` signed URLs
- `/storage/...` URLs
- server-specific absolute paths such as `/var/www/...`
- the old `/data/uploads/sales/...` public URL

A separate media table is only needed if the product later requires multiple files per category, media metadata, per-file ownership, audit history, or independent deletion. None of those requirements are part of the current Sales API.

## Verification checklist

Backend and deployment:

- `php artisan route:list --name=storage` shows `storage.local`.
- `php artisan config:show filesystems.disks.local` shows the private root and `serve` enabled.
- Creating a Sale stores files under `storage/app/private/sales`.
- No new file appears under `public/data/uploads/sales`.
- The database stores a relative `sales/...` path.
- The API returns an absolute URL containing expiration and signature parameters.
- Opening the unchanged signed URL returns the file.
- Changing the path or signature returns `403`.
- The URL returns `403` after expiration.
- A Sale with a missing file returns `null` for that media field.

Frontend:

- Upload requests use `FormData` and do not force a multipart `Content-Type` boundary.
- The UI uses API-returned URLs directly.
- `null` media values do not create broken image requests.
- A stale URL triggers a Sale refresh instead of repeated retries against the same URL.
- Signed URLs are not written to persistent client storage or logs.

The existing focused PHPUnit coverage is in `tests/Feature/SaleApiTest.php`, including `test_sale_media_is_stored_privately_and_returned_as_temporary_links`.
