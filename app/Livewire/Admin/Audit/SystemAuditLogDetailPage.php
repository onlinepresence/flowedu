<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Models\SystemAudit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SystemAuditLogDetailPage extends Component
{
    public SystemAudit $log;

    public function mount(string $uuid): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdminOwner() || $user->adminRoleSlug() === 'system_admin'), 403);

        $this->log = SystemAudit::query()
            ->with(['user'])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function toggleFlag(): void
    {
        $this->log->update(['is_flagged' => !$this->log->is_flagged]);
        $this->log->refresh();
        $this->dispatch('toast', message: $this->log->is_flagged ? __('Log flagged for attention.') : __('Log unflagged.'), type: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.audit.system-audit-log-detail-page')
            ->layout('components.layouts.admin', [
                'title' => __('System Audit Detail'),
                'headerTitle' => __('System Audit Detail'),
                'headerDescription' => __('View detailed client payload, changes metadata, and performance context.'),
            ]);
    }
}
