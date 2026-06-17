<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Backup;
use App\Services\Backup\DatabaseBackupService;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

final class BackupIndex extends Component
{
    use DispatchesCollegeToasts;

    public ?string $restoreFilePond = null;

    /**
     * @return Collection<int, Backup>
     */
    public function getBackupsProperty(): Collection
    {
        return Backup::query()
            ->with('creator')
            ->latest('created_at')
            ->limit(50)
            ->get();
    }

    public function createBackup(DatabaseBackupService $backups): void
    {
        $result = $backups->createBackup(auth()->user());
        if ($result['ok'] ?? false) {
            $this->collegeToast(__('Database backup created successfully.'));
        } else {
            $this->collegeToast($result['message'] ?? __('Backup failed.'), 'danger');
        }
    }

    public function restoreDatabase(DatabaseBackupService $backups): void
    {
        if (! auth()->user()?->isAdminOwner()) {
            $this->collegeToast(__('Only the owner administrator can restore the database.'), 'danger');

            return;
        }

        $this->validate([
            'restoreFilePond' => ['required', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->restoreFilePond, $userId)) {
            $this->addError('restoreFilePond', __('The uploaded file is invalid.'));

            return;
        }

        $name = strtolower((string) basename($this->restoreFilePond));
        if (! str_ends_with($name, '.sql')) {
            $this->addError('restoreFilePond', __('The file must be a .sql dump.'));

            return;
        }

        $relative = FilepondPendingFile::moveToLocalDisk($this->restoreFilePond, $userId, 'temp-restore');
        if ($relative === null) {
            $this->addError('restoreFilePond', __('The uploaded file could not be moved for restore.'));

            return;
        }
        $fullPath = Storage::disk('local')->path($relative);

        try {
            if (! $backups->supportsMysqlDump()) {
                $this->collegeToast(__('Restore requires MySQL; it is not available in this environment.'), 'danger');

                return;
            }

            $backups->tryPreRestoreBackup(auth()->user());
            $backups->restoreFromSqlFile($fullPath);
            $this->collegeToast(__('Database restored successfully. A pre-restore backup was attempted.'));
        } catch (\Throwable $e) {
            $this->collegeToast(__('Restore failed: :msg', ['msg' => $e->getMessage()]), 'danger');
        } finally {
            Storage::disk('local')->delete($relative);
            $this->restoreFilePond = null;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.settings.backup-index', [
            'backups' => $this->backups,
            'canRestore' => auth()->user()?->isAdminOwner() ?? false,
        ])->layout('components.layouts.admin', [
            'title' => __('Backup'),
            'headerTitle' => __('System Backup & Restore'),
            'headerDescription' => __('Create complete SQL backups of your system database or restore from a previously exported backup file.'),
        ]);
    }
}
