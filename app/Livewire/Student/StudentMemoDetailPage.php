<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Memo;
use App\Models\MemoTracking;
use App\Notifications\CollegeNotification;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class StudentMemoDetailPage extends Component
{
    public Memo $memo;

    public function mount(Memo $memo): void
    {
        // Enforce visibility check
        abort_unless($memo->canBeViewedBy(auth()->user()), 403);

        $this->memo = $memo;

        // Record viewed_at
        $receipt = $memo->readReceipts()->where('user_id', auth()->id())->first();
        if ($receipt && is_null($receipt->viewed_at)) {
            $receipt->update(['viewed_at' => now()]);
        }
    }

    public function acknowledgeMemo(): void
    {
        $user = auth()->user();

        $receipt = $this->memo->readReceipts()->where('user_id', $user->id)->first();
        if ($receipt && is_null($receipt->acknowledged_at)) {
            $receipt->update(['acknowledged_at' => now()]);
        }

        // Write to tracking
        $exists = MemoTracking::query()
            ->where('memo_id', $this->memo->id)
            ->where('forwarded_by', $user->id)
            ->where('action', 'acknowledged')
            ->exists();

        if (!$exists) {
            MemoTracking::query()->create([
                'memo_id' => $this->memo->id,
                'forwarded_by' => $user->id,
                'action' => 'acknowledged',
                'remarks' => 'Memo read and acknowledged.',
            ]);
        }

        CollegeFlash::forNextRequestToo('status', __('Memo acknowledged.'));
        $this->redirect(route('student.memos.show', $this->memo->id), navigate: true);
    }

    public function downloadAttachment(int $id)
    {
        $attachment = $this->memo->attachments()->findOrFail($id);
        
        if (Storage::disk('local')->exists($attachment->file_path)) {
            return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
        }

        $this->addError('download', __('Attachment file could not be found.'));
    }

    public function render(): View
    {
        return view('livewire.student.student-memo-detail-page')
            ->layout('components.layouts.student', ['title' => __('Memo Details'), 'hideHeader' => true]);
    }
}
