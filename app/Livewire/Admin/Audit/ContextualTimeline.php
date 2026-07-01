<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Models\SystemAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ContextualTimeline extends Component
{
    public Model $model;
    public bool $showAll = false;

    public function mount(Model $model): void
    {
        abort_unless(auth()->user()?->hasAdminPermission('view_audit_logs'), 403);
        $this->model = $model;
    }

    public function toggleShowAll(): void
    {
        $this->showAll = !$this->showAll;
    }

    public function render(): View
    {
        $query = SystemAudit::query()
            ->where('auditable_type', get_class($this->model))
            ->where('auditable_id', $this->model->getKey())
            ->with('user')
            ->orderBy('created_at', 'desc');

        $totalCount = $query->count();

        $logs = $this->showAll ? $query->get() : $query->limit(5)->get();

        return view('livewire.admin.audit.contextual-timeline', [
            'logs' => $logs,
            'totalCount' => $totalCount,
        ]);
    }
}
