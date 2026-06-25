<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Memos;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Memo;
use App\Models\MemoTracking;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\CollegeNotification;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class MemoDetailPage extends Component
{
    use WithFileUploads;

    public Memo $memo;

    // Actions state
    public bool $showForwardModal = false;
    public bool $showReturnModal = false;
    public $signature_file;

    // Forward form state
    public string $forward_recipient_type = 'department';
    public ?string $forward_recipient_entity_id = null;
    public ?string $forward_recipient_role_id = null;
    public string $forward_remarks = '';

    // Return form state
    public string $return_remarks = '';
    public string $signature_remarks = '';

    public function mount(Memo $memo): void
    {
        // Enforce basic permissions
        abort_unless(auth()->user()?->hasAdminPermission('nav_memos'), 403);

        // Check if user is authorized to view this specific memo
        abort_unless($memo->canBeViewedBy(auth()->user()), 403);

        $this->memo = $memo;

        // Update read receipt viewed_at timestamp if recipient
        $receipt = $memo->readReceipts()->where('user_id', auth()->id())->first();
        if ($receipt && is_null($receipt->viewed_at)) {
            $receipt->update(['viewed_at' => now()]);
        }
    }

    public function signMemo(): void
    {
        $user = auth()->user();

        $signatory = $this->memo->signatories()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        // Fallback for legacy
        if (!$signatory && $this->memo->signing_user_id === $user->id && $this->memo->status === 'pending_signature') {
            $signatory = $this->memo->signatories()->firstOrCreate([
                'user_id' => $user->id,
            ], [
                'status' => 'pending',
                'step_number' => 1,
            ]);
        }

        // Enforce sequential signature: their step must be the minimum pending step!
        $currentPendingSignatory = $this->memo->signatories()
            ->where('status', 'pending')
            ->orderBy('step_number', 'asc')
            ->first();

        if (!$currentPendingSignatory || (int)$currentPendingSignatory->user_id !== (int)$user->id) {
            $this->addError('signature_remarks', __('It is not your turn to sign this memo.'));
            return;
        }

        abort_unless($signatory && $this->memo->status === 'pending_signature', 403);

        $signaturePath = null;
        if ($this->signature_file) {
            $signaturePath = $this->signature_file->store('signatures', 'public');
        }

        DB::transaction(function () use ($user, $signatory, $signaturePath) {
            $signatory->update([
                'status' => 'signed',
                'signed_at' => now(),
                'signature_path' => $signaturePath,
                'remarks' => $this->signature_remarks ?: 'Approved and signed.',
            ]);

            // Save tracking log
            MemoTracking::query()->create([
                'memo_id' => $this->memo->id,
                'from_entity_type' => 'user',
                'from_entity_id' => $user->id,
                'to_entity_type' => $this->memo->recipient_type,
                'to_entity_id' => $this->memo->recipient_type === 'role' ? $this->memo->recipient_role_id : $this->memo->recipient_entity_id,
                'forwarded_by' => $user->id,
                'action' => 'signed',
                'remarks' => $this->signature_remarks ?: 'Approved and signed.',
            ]);

            // Check if all signatories have signed
            $pendingExists = $this->memo->signatories()->where('status', 'pending')->exists();
            
            if (!$pendingExists) {
                $this->memo->update([
                    'status' => 'sent',
                    'signing_user_id' => null,
                ]);

                // Log final dispatch tracking
                MemoTracking::query()->create([
                    'memo_id' => $this->memo->id,
                    'forwarded_by' => $user->id,
                    'action' => 'sent',
                    'remarks' => 'Memo fully signed and dispatched.',
                ]);

                // Seed read receipts
                $recipients = $this->memo->resolveTargetRecipients();
                foreach ($recipients as $recipient) {
                    \App\Models\MemoReadReceipt::query()->firstOrCreate([
                        'memo_id' => $this->memo->id,
                        'user_id' => $recipient->id,
                    ]);
                }

                // Notify sender
                if ($this->memo->sender) {
                    $this->memo->sender->notify(new CollegeNotification(
                        'Memo Signed & Sent',
                        "Your memo '{$this->memo->title}' has been fully signed and dispatched.",
                        route('admin.memos.show', $this->memo->id)
                    ));
                }

                // Notify recipients
                $this->notifyRecipients();
            } else {
                // Advance turn to the next pending signatory sequential step!
                $nextPending = $this->memo->signatories()
                    ->where('status', 'pending')
                    ->orderBy('step_number', 'asc')
                    ->first();
                if ($nextPending) {
                    $this->memo->update([
                        'signing_user_id' => $nextPending->user_id,
                    ]);
                }

                if ($this->memo->sender) {
                    $this->memo->sender->notify(new CollegeNotification(
                        'Memo Partially Signed',
                        "{$user->name} has signed your memo '{$this->memo->title}'. Waiting on other signatories.",
                        route('admin.memos.show', $this->memo->id)
                    ));
                }
            }
        });

        $this->signature_remarks = '';
        $this->signature_file = null;
        CollegeFlash::forNextRequestToo('status', __('Memo signed.'));
        $this->redirect(route('admin.memos.show', $this->memo->id), navigate: true);
    }

    public function openForward(): void
    {
        $this->forward_recipient_type = 'department';
        $this->forward_recipient_entity_id = null;
        $this->forward_recipient_role_id = null;
        $this->forward_remarks = '';
        $this->showForwardModal = true;
    }

    public function closeForward(): void
    {
        $this->showForwardModal = false;
    }

    public function forwardMemo(): void
    {
        abort_unless(auth()->user()?->hasAdminPermission('forward_memo'), 403);

        $this->validate([
            'forward_recipient_type' => ['required', 'in:user,department,faculty,role'],
            'forward_recipient_entity_id' => [
                'required_if:forward_recipient_type,user,department,faculty',
                'nullable',
            ],
            'forward_recipient_role_id' => [
                'required_if:forward_recipient_type,role',
                'nullable',
                'exists:user_roles,id',
            ],
            'forward_remarks' => ['nullable', 'string'],
        ]);

        $user = auth()->user();

        DB::transaction(function () use ($user) {
            // Log old routing entity details before updating
            $oldType = $this->memo->recipient_type;
            $oldId = $this->memo->recipient_type === 'role' ? $this->memo->recipient_role_id : $this->memo->recipient_entity_id;

            $this->memo->update([
                'recipient_type' => $this->forward_recipient_type,
                'recipient_entity_id' => $this->forward_recipient_type === 'role' ? null : $this->forward_recipient_entity_id,
                'recipient_role_id' => $this->forward_recipient_type === 'role' ? $this->forward_recipient_role_id : null,
                'status' => 'sent',
            ]);

            // Save tracking log
            MemoTracking::query()->create([
                'memo_id' => $this->memo->id,
                'from_entity_type' => $oldType,
                'from_entity_id' => $oldId,
                'to_entity_type' => $this->forward_recipient_type,
                'to_entity_id' => $this->forward_recipient_type === 'role' ? $this->forward_recipient_role_id : $this->forward_recipient_entity_id,
                'forwarded_by' => $user->id,
                'action' => 'forwarded',
                'remarks' => $this->forward_remarks ?: 'Memo forwarded.',
            ]);

            // Re-seed/update read receipts for the new targets
            $recipients = $this->memo->resolveTargetRecipients();
            foreach ($recipients as $recipient) {
                \App\Models\MemoReadReceipt::query()->firstOrCreate([
                    'memo_id' => $this->memo->id,
                    'user_id' => $recipient->id,
                ]);
            }

            // Notify new recipients
            $this->notifyRecipients();
        });

        CollegeFlash::forNextRequestToo('status', __('Memo forwarded successfully.'));
        $this->closeForward();
        $this->redirect(route('admin.memos.show', $this->memo->id), navigate: true);
    }

    public function openReturn(): void
    {
        $this->return_remarks = '';
        $this->showReturnModal = true;
    }

    public function closeReturn(): void
    {
        $this->showReturnModal = false;
    }

    public function returnMemo(): void
    {
        $user = auth()->user();
        
        $this->validate([
            'return_remarks' => ['required', 'string'],
        ]);

        $signatory = $this->memo->signatories()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        // Fallback for legacy
        if (!$signatory && $this->memo->signing_user_id === $user->id && $this->memo->status === 'pending_signature') {
            $signatory = $this->memo->signatories()->firstOrCreate([
                'user_id' => $user->id,
            ], [
                'status' => 'pending',
            ]);
        }

        abort_unless($signatory && $this->memo->status === 'pending_signature', 403);

        DB::transaction(function () use ($user, $signatory) {
            $signatory->update([
                'status' => 'rejected',
                'remarks' => $this->return_remarks,
            ]);

            $this->memo->update([
                'status' => 'draft',
            ]);

            // Save tracking log
            MemoTracking::query()->create([
                'memo_id' => $this->memo->id,
                'from_entity_type' => 'user',
                'from_entity_id' => $user->id,
                'to_entity_type' => 'user',
                'to_entity_id' => $this->memo->sender_id,
                'forwarded_by' => $user->id,
                'action' => 'returned',
                'remarks' => $this->return_remarks,
            ]);

            // Reset other signatories' statuses back to pending since it was returned to draft
            $this->memo->signatories()->update([
                'status' => 'pending',
                'signed_at' => null,
            ]);

            // Notify sender
            if ($this->memo->sender) {
                $this->memo->sender->notify(new CollegeNotification(
                    'Memo Returned / Rejected',
                    "Your memo '{$this->memo->title}' has been returned by {$user->name} with remarks: '{$this->return_remarks}'",
                    route('admin.memos.show', $this->memo->id)
                ));
            }
        });

        CollegeFlash::forNextRequestToo('status', __('Memo returned to sender.'));
        $this->closeReturn();
        $this->redirect(route('admin.memos.show', $this->memo->id), navigate: true);
    }

    public function acknowledgeMemo(): void
    {
        $user = auth()->user();

        $receipt = $this->memo->readReceipts()->where('user_id', $user->id)->first();
        if ($receipt && is_null($receipt->acknowledged_at)) {
            $receipt->update(['acknowledged_at' => now()]);
        }

        // Check if already acknowledged by this user in tracking
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
        $this->redirect(route('admin.memos.show', $this->memo->id), navigate: true);
    }

    private function notifyRecipients(): void
    {
        $senderName = auth()->user()?->name ?: 'System';
        $title = 'Forwarded Memo: ' . $this->memo->title;
        $message = "A memo has been forwarded to you by {$senderName}.";
        $url = route('admin.memos.show', $this->memo->id);

        if ($this->memo->recipient_type === 'user' && $this->memo->recipient_entity_id) {
            $recipient = User::query()->find($this->memo->recipient_entity_id);
            if ($recipient) {
                $recipient->notify(new CollegeNotification($title, $message, $url));
            }
        } elseif ($this->memo->recipient_type === 'department' && $this->memo->recipient_entity_id) {
            $users = User::query()->where('active', true)
                ->where(function ($q) {
                    $q->whereHas('admin', fn($a) => $a->where('department_id', $this->memo->recipient_entity_id))
                      ->orWhereHas('teacher', fn($t) => $t->where('department_id', $this->memo->recipient_entity_id));
                })->get();
            foreach ($users as $u) {
                $u->notify(new CollegeNotification($title, $message, $url));
            }
        } elseif ($this->memo->recipient_type === 'faculty' && $this->memo->recipient_entity_id) {
            $users = User::query()->where('active', true)
                ->where(function ($q) {
                    $q->whereHas('admin', fn($a) => $a->where('faculty_id', $this->memo->recipient_entity_id));
                })->get();
            foreach ($users as $u) {
                $u->notify(new CollegeNotification($title, $message, $url));
            }
        } elseif ($this->memo->recipient_type === 'role' && $this->memo->recipient_role_id) {
            $users = User::query()->where('active', true)
                ->whereHas('admin', fn($a) => $a->where('type', $this->memo->recipient_role_id))
                ->get();
            foreach ($users as $u) {
                $u->notify(new CollegeNotification($title, $message, $url));
            }
        }
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
        $departments = Department::query()->orderBy('name')->get();
        $faculties = Faculty::query()->orderBy('name')->get();
        $roles = UserRole::query()->orderBy('display_name')->get();
        
        $users = User::query()
            ->where('active', true)
            ->where('type', 'admin')
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get();

        return view('livewire.admin.memos.memo-detail-page', [
            'departments' => $departments,
            'faculties' => $faculties,
            'roles' => $roles,
            'users' => $users,
        ])->layout('components.layouts.admin', ['title' => __('Memo Details')]);
    }
}
