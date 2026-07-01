<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Models\SystemAudit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SystemAuditLogDetailPage extends Component
{
    public SystemAudit $log;

    public ?\App\Models\Invoice $resolvedInvoice = null;
    public ?\App\Models\Student $resolvedStudent = null;
    public ?\App\Models\Memo $resolvedMemo = null;
    public ?\App\Models\LeaveRequest $resolvedLeaveRequest = null;
    public bool $isTargetDeleted = false;

    public function mount(string $uuid): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdminOwner() || $user->adminRoleSlug() === 'system_admin'), 403);

        $this->log = SystemAudit::query()
            ->with(['user'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $type = $this->log->auditable_type;
        $id = $this->log->auditable_id;

        if ($type && $id) {
            try {
                if ($type === \App\Models\Invoice::class) {
                    $this->resolvedInvoice = \App\Models\Invoice::with(['items.product', 'expenditures'])->find($id);
                } elseif ($type === \App\Models\Student::class) {
                    $this->resolvedStudent = \App\Models\Student::find($id);
                } elseif ($type === \App\Models\Memo::class) {
                    $this->resolvedMemo = \App\Models\Memo::find($id);
                } elseif ($type === \App\Models\LeaveRequest::class) {
                    $this->resolvedLeaveRequest = \App\Models\LeaveRequest::with(['user', 'staffLeaveType'])->find($id);
                }

                // If not found, check if it's generic deleted
                if (!$this->resolvedInvoice && !$this->resolvedStudent && !$this->resolvedMemo && !$this->resolvedLeaveRequest) {
                    if (class_exists($type)) {
                        $resolvedGeneric = $type::find($id);
                        if (!$resolvedGeneric) {
                            $this->isTargetDeleted = true;
                        }
                    } else {
                        $this->isTargetDeleted = true;
                    }
                }
            } catch (\Throwable $e) {
                $this->isTargetDeleted = true;
            }
        }
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
