<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Memo;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class StudentMemosPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $activeTab = 'all'; // all, unread, acknowledged

    public function render(): View
    {
        $user = auth()->user();
        
        $query = Memo::query()
            ->whereIn('status', ['sent', 'archived'])
            ->whereHas('readReceipts', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['sender'])
            ->orderBy('updated_at', 'desc');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->activeTab === 'unread') {
            $query->whereHas('readReceipts', function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNull('acknowledged_at');
            });
        } elseif ($this->activeTab === 'acknowledged') {
            $query->whereHas('readReceipts', function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNotNull('acknowledged_at');
            });
        }

        $memos = $query->paginate(10);

        return view('livewire.student.student-memos-page', [
            'memos' => $memos,
        ])->layout('components.layouts.student', ['title' => __('Memos'), 'hideHeader' => true]);
    }
}
