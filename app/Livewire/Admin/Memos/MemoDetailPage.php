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

    // Edit/Resubmit state
    public bool $isEditing = false;
    public string $editTitle = '';
    public string $editContent = '';
    public array $editSelectedSignatories = [];
    public bool $editSelfSign = false;
    public array $editCcUsers = [];
    public array $editCcDepartments = [];
    public array $editCcRoles = [];
    public string $resubmissionChoice = 'restart'; // restart or resume
    public string $signatorySearch = '';
    public string $ccSearch = '';

    public $attachments = []; // Staging new attachments
    public array $deletedAttachmentIds = []; // Tracking existing attachment IDs slated for deletion

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

        // Initialize edit states if draft
        if ($this->memo->status === 'draft') {
            $this->editTitle = $this->memo->title;
            $this->editContent = $this->memo->content;
            $this->editSelfSign = $this->memo->signatories()->where('user_id', auth()->id())->exists();
            $this->editSelectedSignatories = $this->memo->signatories()
                ->where('user_id', '!=', auth()->id())
                ->orderBy('step_number')
                ->pluck('user_id')
                ->toArray();
            
            $cc = $this->memo->cc_recipients;
            if (is_array($cc)) {
                $this->editCcUsers = $cc['users'] ?? [];
                $this->editCcDepartments = $cc['departments'] ?? [];
                $this->editCcRoles = $cc['roles'] ?? [];
            }
        }
    }

    public function addSignatory(int $userId): void
    {
        $memos_multiple_signatories = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_multiple_signatories')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$memos_multiple_signatories) {
            $this->editSelectedSignatories = [$userId];
        } else {
            if (!in_array($userId, $this->editSelectedSignatories)) {
                $this->editSelectedSignatories[] = $userId;
            }
        }
        $this->signatorySearch = '';
    }

    public function removeSignatory(int $userId): void
    {
        $this->editSelectedSignatories = array_values(array_diff($this->editSelectedSignatories, [$userId]));
    }

    public function addCcUser(int $userId): void
    {
        if (!in_array($userId, $this->editCcUsers)) {
            $this->editCcUsers[] = $userId;
        }
        $this->ccSearch = '';
    }

    public function removeCcUser(int $userId): void
    {
        $this->editCcUsers = array_values(array_diff($this->editCcUsers, [$userId]));
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
        abort_if($this->memo->confidentiality_level === 'public', 403, 'Public memos cannot be forwarded.');

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
        abort_if($this->memo->confidentiality_level === 'public', 403, 'Public memos cannot be forwarded.');

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

    public function toggleEditing(): void
    {
        $this->isEditing = !$this->isEditing;
        $this->attachments = [];
        $this->deletedAttachmentIds = [];

        if ($this->isEditing) {
            $this->editTitle = $this->memo->title;
            $this->editContent = $this->memo->content;
            $this->editSelfSign = $this->memo->signatories()->where('user_id', auth()->id())->exists();
            $this->editSelectedSignatories = $this->memo->signatories()
                ->where('user_id', '!=', auth()->id())
                ->orderBy('step_number')
                ->pluck('user_id')
                ->toArray();
            
            $cc = $this->memo->cc_recipients;
            if (is_array($cc)) {
                $this->editCcUsers = $cc['users'] ?? [];
                $this->editCcDepartments = $cc['departments'] ?? [];
                $this->editCcRoles = $cc['roles'] ?? [];
            }
        }
    }

    public function stageAttachmentDeletion(int $attachmentId): void
    {
        if (!in_array($attachmentId, $this->deletedAttachmentIds)) {
            $this->deletedAttachmentIds[] = $attachmentId;
        }
    }

    public function unstageAttachmentDeletion(int $attachmentId): void
    {
        $this->deletedAttachmentIds = array_values(array_diff($this->deletedAttachmentIds, [$attachmentId]));
    }

    public function resubmitMemo(string $submitType = 'send'): void
    {
        $memos_require_signature = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_require_signature')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $memos_multiple_signatories = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_multiple_signatories')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $rules = [
            'editTitle' => ['required', 'string', 'max:255'],
            'editContent' => ['required', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:10240'], // 10MB limit
        ];

        if ($submitType !== 'draft') {
            if ($memos_require_signature && !$this->editSelfSign) {
                $rules['editSelectedSignatories'] = ['required', 'array', 'min:1'];
            } else {
                $rules['editSelectedSignatories'] = ['nullable', 'array'];
            }
            $rules['editSelectedSignatories.*'] = ['exists:users,id'];
        }

        $this->validate($rules);

        $user = auth()->user();

        if ($submitType !== 'draft' && !$memos_multiple_signatories && count(array_filter($this->editSelectedSignatories)) > 1) {
            $this->addError('editSelectedSignatories', __('Only one signatory is allowed.'));
            return;
        }

        DB::transaction(function () use ($user, $submitType) {
            $this->memo->update([
                'title' => $this->editTitle,
                'content' => $this->editContent,
                'cc_recipients' => [
                    'users' => array_map('intval', array_filter($this->editCcUsers)),
                    'departments' => array_map('intval', array_filter($this->editCcDepartments)),
                    'roles' => array_map('intval', array_filter($this->editCcRoles)),
                ],
            ]);

            // Delete attachments staged for removal
            foreach ($this->deletedAttachmentIds as $attId) {
                $att = $this->memo->attachments()->find($attId);
                if ($att) {
                    \Illuminate\Support\Facades\Storage::delete($att->file_path);
                    $att->delete();
                }
            }

            // Save newly added attachments
            foreach ($this->attachments as $file) {
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $filePath = $file->store('memos');

                $this->memo->attachments()->create([
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                ]);
            }

            // Prepare signatories list
            $signatories = $this->editSelectedSignatories;
            if ($this->editSelfSign) {
                $signatories[] = $user->id;
            }
            $signatories = array_unique(array_filter($signatories));

            // Sync signatories pivot table to preserve existing signatories' status/remarks
            $existingSigs = $this->memo->signatories()->get()->keyBy('user_id');
            $this->memo->signatories()->whereNotIn('user_id', $signatories)->delete();

            $step = 1;
            foreach ($signatories as $sId) {
                $isSelf = (int)$sId === (int)$user->id && $this->editSelfSign;
                if ($existingSigs->has($sId)) {
                    $existingSigs[$sId]->update([
                        'step_number' => $step++,
                    ]);
                } else {
                    $this->memo->signatories()->create([
                        'user_id' => $sId,
                        'step_number' => $step++,
                        'status' => $isSelf ? 'signed' : 'pending',
                        'signed_at' => $isSelf ? now() : null,
                        'remarks' => $isSelf ? 'Self-signed.' : null,
                    ]);
                }
            }

            if ($submitType === 'draft') {
                return;
            }

            // Apply workflow resubmission options if we are submitting
            $hasRejections = $this->memo->signatories()->where('status', 'rejected')->exists();

            if ($hasRejections && $this->resubmissionChoice === 'restart') {
                // Restart workflow: reset all signatories (except self) back to pending
                foreach ($signatories as $sId) {
                    $isSelf = (int)$sId === (int)$user->id && $this->editSelfSign;
                    $this->memo->signatories()->where('user_id', $sId)->update([
                        'status' => $isSelf ? 'signed' : 'pending',
                        'signed_at' => $isSelf ? now() : null,
                        'remarks' => $isSelf ? 'Self-signed at creation.' : null,
                    ]);
                }
            } elseif ($hasRejections && $this->resubmissionChoice === 'resume') {
                // Resume workflow: set the rejected signatories back to pending
                $this->memo->signatories()->where('status', 'rejected')->update([
                    'status' => 'pending',
                    'signed_at' => null,
                    'remarks' => null,
                ]);
            }

            // Determine status
            $pendingExists = $this->memo->signatories()->where('status', 'pending')->exists();
            $status = $pendingExists ? 'pending_signature' : 'sent';

            $firstPending = $this->memo->signatories()
                ->where('status', 'pending')
                ->orderBy('step_number', 'asc')
                ->first();
            
            $this->memo->update([
                'status' => $status,
                'signing_user_id' => $firstPending ? $firstPending->user_id : null,
            ]);

            // Save tracking log
            MemoTracking::query()->create([
                'memo_id' => $this->memo->id,
                'forwarded_by' => $user->id,
                'action' => 'sent',
                'remarks' => $status === 'pending_signature' ? 'Resubmitted for review and signature.' : 'Memo dispatched.',
            ]);

            // If dispatched, seed read receipts
            if ($status === 'sent') {
                $recipients = $this->memo->resolveTargetRecipients();
                foreach ($recipients as $recipient) {
                    \App\Models\MemoReadReceipt::query()->firstOrCreate([
                        'memo_id' => $this->memo->id,
                        'user_id' => $recipient->id,
                    ]);
                }
                $ccRecipients = $this->memo->resolveCCRecipients();
                foreach ($ccRecipients as $ccUser) {
                    \App\Models\MemoReadReceipt::query()->firstOrCreate([
                        'memo_id' => $this->memo->id,
                        'user_id' => $ccUser->id,
                    ]);
                }
                $this->notifyRecipients();
            } else {
                // Notify first pending signatory
                if ($firstPending) {
                    $firstPending->user->notify(new CollegeNotification(
                        'Memo Signature Request',
                        "{$user->name} has requested your signature on: '{$this->memo->title}'",
                        route('admin.memos.show', $this->memo->id)
                    ));
                }
            }
        });

        $this->attachments = [];
        $this->deletedAttachmentIds = [];
        $this->isEditing = false;
        CollegeFlash::forNextRequestToo('status', __('Memo resubmitted successfully.'));
        $this->redirect(route('admin.memos.show', $this->memo->id), navigate: true);
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
