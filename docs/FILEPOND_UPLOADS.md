# FilePond uploads (Livewire)

## Overview

File uploads use [FilePond](https://pqina.nl/filepond/) with a shared Blade component and a small authenticated API. Files land in `storage/app/filepond-tmp/{user_id}/…` until the Livewire action moves them to a final disk path.

## Component

```blade
<x-filepond
    field="logoFilepondPath"
    purpose="school_logo"
    :label="__('Logo')"
    accept="image/jpeg,image/png,image/webp,image/gif"
/>
```

- **`field`** — Name of the Livewire string property holding the pending relative path (under the local disk) returned by `POST /__college/filepond/process`.
- **`purpose`** — Server-side validation preset in [`FilepondController`](../app/Http/Controllers/FilepondController.php) (`school_logo`, `profile_photo`, `admin_profile_photo`, `backup_upload`, or default).
- **`accept`** — Optional MIME list for the FilePond client (server validates independently).

The component renders a hidden `wire:model.live` input synced from JavaScript so Livewire’s DOM diff does not fight FilePond. The visible pond sits in a `wire:ignore` wrapper.

## Finalizing uploads

Use [`FilepondPendingFile::moveToPublicDisk()`](../app/Support/FilepondPendingFile.php) or `moveToLocalDisk()` after validation; then clear the Livewire property. Paths must start with `filepond-tmp/{auth_id}/` and exist on the `local` disk.

## Routes

| Method | Name | Action |
|--------|------|--------|
| POST | `college.filepond.process` | Store temp file, return plain-text path |
| DELETE | `college.filepond.revert` | Delete temp file (body = path) |

Both require `auth`. CSRF: `X-CSRF-TOKEN` header (handled in [`resources/js/filepond-college.js`](../resources/js/filepond-college.js)).

## Livewire refreshes

Initialization runs on `livewire:init`, `morph.updated` (new nodes only), and `livewire:navigated`. Each pond root sets `data-filepond-bound` so instances are not duplicated while the ignored subtree is preserved across morphs.
