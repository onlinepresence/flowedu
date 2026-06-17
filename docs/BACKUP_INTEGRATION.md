# Database backup (MySQL)

Backups use `mysqldump` and restores use `mysql` via [`App\Services\Backup\DatabaseBackupService`](../app/Services/Backup/DatabaseBackupService.php). Dump files are stored on the **`local` filesystem disk** (see [`config/filesystems.php`](../config/filesystems.php): `storage/app/private/…`), never under `public/`.

## Configuration

Set full paths on Windows (Laragon) if the binaries are not on `PATH`:

```env
MYSQLDUMP_PATH=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe
MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe
```

See also [`config/backup.php`](../config/backup.php).

## Access control

- **Create backup**, **history**, and **download**: `college.licence:system_admin` (same as the Backup settings page).
- **Restore**: **owner** admin only (`User::isAdminOwner()`), matching legacy behaviour.

## Automated tests (CI)

PHPUnit uses **sqlite** (`phpunit.xml`). The real service reports that dump/restore are unsupported; feature tests bind [`Tests\Support\FakeDatabaseBackupService`](../tests/Support/FakeDatabaseBackupService.php) to exercise UI without shelling out.

## Manual integration test (MySQL)

1. Point `.env` at a disposable MySQL database.
2. Run `php artisan migrate:fresh --seed` (optional).
3. Sign in as a system-admin owner, open **Settings → Backup**, create a backup, download the file.
4. Optionally restore from a small SQL file on a **throwaway** database only.
