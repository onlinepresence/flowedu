<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Models\SystemAudit;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SystemAuditLogsPage extends Component
{
    use WithPagination;

    public string $activeTab = 'all'; // 'all', 'flagged'

    // Filters
    public string $searchUser = '';
    public string $selectedAction = '';
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdminOwner() || $user->adminRoleSlug() === 'system_admin'), 403);
    }

    public function toggleFlag(int $id): void
    {
        $log = SystemAudit::findOrFail($id);
        $log->update(['is_flagged' => !$log->is_flagged]);
        $this->dispatch('toast', message: $log->is_flagged ? __('Log flagged for attention.') : __('Log unflagged.'), type: 'success');
    }

    public function render(): View
    {
        $query = SystemAudit::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by Tab
        if ($this->activeTab === 'flagged') {
            $query->where('is_flagged', true);
        }

        // Filter by User
        if ($this->searchUser) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->searchUser . '%')
                  ->orWhere('username', 'like', '%' . $this->searchUser . '%');
            });
        }

        // Filter by Action
        if ($this->selectedAction) {
            $query->where('action', 'like', '%' . $this->selectedAction . '%');
        }

        // Filter by Date Range
        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        // Get unique actions list for dropdown filter
        $availableActions = SystemAudit::query()
            ->select('action')
            ->distinct()
            ->pluck('action');

        return view('livewire.admin.audit.system-audit-logs-page', [
            'logs' => $query->paginate(20),
            'availableActions' => $availableActions,
        ])->layout('components.layouts.admin', [
            'title' => __('System Audit Logs'),
            'headerTitle' => __('System Audit Logs'),
            'headerDescription' => __('Monitor administrative actions, security flags, and configuration audits.'),
        ]);
    }
}
