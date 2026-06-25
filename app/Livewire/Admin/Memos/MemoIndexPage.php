<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Memos;

use App\Models\Admin;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\MemoTracking;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\CollegeNotification;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;

class MemoIndexPage extends Component
{
    use WithFileUploads;

    public string $activeTab = 'inbox'; // inbox, outbox, pending, drafts
    public string $search = '';
    public string $confidentiality = 'all'; // all, public, internal, confidential

    // Create Form state
    public bool $showCreateModal = false;
    public string $title = '';
    public string $content = '';
    public string $confidentiality_level = 'internal';
    public string $recipient_type = 'department'; // user, department, faculty, role
    public ?string $recipient_entity_id = null;
    public ?string $recipient_role_id = null;
    public ?string $signing_user_id = null; // Left for legacy compatibility
    
    // Multi-signatory state
    public array $selected_signatories = [];
    public bool $self_sign = false;
    
    /** @var array */
    public $attachments = []; // file uploads

    protected $queryString = [
        'activeTab' => ['except' => 'inbox'],
        'search' => ['except' => ''],
        'confidentiality' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        // Enforce basic permissions
        abort_unless(auth()->user()?->hasAdminPermission('nav_memos'), 403);
    }

    #[On('open-create-memo')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->js('window.dispatchEvent(new CustomEvent("open-modal", { detail: "create-memo-form" }))');
    }

    public function closeCreate(): void
    {
        $this->js('window.dispatchEvent(new CustomEvent("close-modal", { detail: "create-memo-form" }))');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->title = '';
        $this->content = '';
        $this->confidentiality_level = 'internal';
        $this->recipient_type = 'department';
        $this->recipient_entity_id = null;
        $this->recipient_role_id = null;
        $this->signing_user_id = null;
        $this->selected_signatories = [];
        $this->self_sign = false;
        $this->attachments = [];
    }

    public function saveMemo(string $submitType = 'send'): void
    {
        if (empty($this->selected_signatories) && $this->signing_user_id) {
            $this->selected_signatories = [$this->signing_user_id];
        }

        $memos_require_signature = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_require_signature')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $memos_multiple_signatories = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_multiple_signatories')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'confidentiality_level' => ['required', 'in:public,internal,confidential'],
            'recipient_type' => ['required', 'in:user,department,faculty,role'],
            'recipient_entity_id' => [
                'required_if:recipient_type,user,department,faculty',
                'nullable',
            ],
            'recipient_role_id' => [
                'required_if:recipient_type,role',
                'nullable',
                'exists:user_roles,id',
            ],
            'attachments.*' => ['nullable', 'file', 'max:10240'], // 10MB limit per file
        ];

        if ($submitType !== 'draft') {
            if ($memos_require_signature && !$this->self_sign) {
                $rules['selected_signatories'] = ['required', 'array', 'min:1'];
            } else {
                $rules['selected_signatories'] = ['nullable', 'array'];
            }
            $rules['selected_signatories.*'] = ['exists:users,id'];
        }

        $this->validate($rules);

        $user = auth()->user();

        if ($submitType !== 'draft' && !$memos_multiple_signatories && count(array_filter($this->selected_signatories)) > 1) {
            $this->addError('selected_signatories', __('Only one signatory is allowed.'));
            return;
        }

        // Sender context mapping
        $senderEntityType = 'user';
        $senderEntityId = null;

        if ($user->type === 'admin') {
            $admin = $user->admin;
            if ($admin && $admin->department_id) {
                $senderEntityType = 'department';
                $senderEntityId = $admin->department_id;
            } elseif ($admin && $admin->faculty_id) {
                $senderEntityType = 'faculty';
                $senderEntityId = $admin->faculty_id;
            }
        }

        // Determine status and signatories
        $status = 'sent';
        $signatories = [];

        if ($submitType === 'draft') {
            $status = 'draft';
        } else {
            $signatories = $this->selected_signatories;
            if ($this->self_sign) {
                $signatories[] = $user->id;
            }
            $signatories = array_unique(array_filter($signatories));

            if (!empty($signatories)) {
                $allSigned = true;
                foreach ($signatories as $sId) {
                    if ((int)$sId !== (int)$user->id) {
                        $allSigned = false;
                    }
                }
                $status = $allSigned ? 'sent' : 'pending_signature';
            }
        }

        $firstSignatoryId = !empty($signatories) ? reset($signatories) : null;

        DB::transaction(function () use ($user, $senderEntityType, $senderEntityId, $status, $signatories, $firstSignatoryId) {
            $memo = Memo::query()->create([
                'title' => $this->title,
                'content' => $this->content,
                'sender_id' => $user->id,
                'sender_entity_type' => $senderEntityType,
                'sender_entity_id' => $senderEntityId,
                'recipient_type' => $this->recipient_type,
                'recipient_entity_id' => $this->recipient_type === 'role' ? null : $this->recipient_entity_id,
                'recipient_role_id' => $this->recipient_type === 'role' ? $this->recipient_role_id : null,
                'confidentiality_level' => $this->confidentiality_level,
                'status' => $status,
                'signing_user_id' => $firstSignatoryId,
            ]);

            // Save tracking log
            MemoTracking::query()->create([
                'memo_id' => $memo->id,
                'from_entity_type' => $senderEntityType,
                'from_entity_id' => $senderEntityId,
                'to_entity_type' => $this->recipient_type,
                'to_entity_id' => $this->recipient_type === 'role' ? $this->recipient_role_id : $this->recipient_entity_id,
                'forwarded_by' => $user->id,
                'action' => 'sent',
                'remarks' => $status === 'draft' ? 'Saved as draft.' : ($status === 'pending_signature' ? 'Submitted for review and signature.' : 'Memo dispatched.'),
            ]);

            // Create pivot signatory records
            if ($status !== 'draft' && !empty($signatories)) {
                $step = 1;
                foreach ($signatories as $sId) {
                    $isSelf = (int)$sId === (int)$user->id;
                    $memo->signatories()->create([
                        'user_id' => $sId,
                        'step_number' => $step++,
                        'status' => $isSelf ? 'signed' : 'pending',
                        'signed_at' => $isSelf ? now() : null,
                        'remarks' => $isSelf ? 'Self-signed at creation.' : null,
                    ]);
                }

                // Resolve first pending signatory sequential turn
                $firstPending = $memo->signatories()
                    ->where('status', 'pending')
                    ->orderBy('step_number', 'asc')
                    ->first();
                if ($firstPending) {
                    $memo->update(['signing_user_id' => $firstPending->user_id]);
                }
            }

            // If dispatched, seed read receipts for recipients
            if ($status === 'sent') {
                $recipients = $memo->resolveTargetRecipients();
                foreach ($recipients as $recipient) {
                    \App\Models\MemoReadReceipt::query()->firstOrCreate([
                        'memo_id' => $memo->id,
                        'user_id' => $recipient->id,
                    ]);
                }
            }

            // Upload attachments
            foreach ($this->attachments as $file) {
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $filePath = $file->store('memos');

                MemoAttachment::query()->create([
                    'memo_id' => $memo->id,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                ]);
            }

            // Send Notifications
            if ($status === 'pending_signature') {
                foreach ($signatories as $sId) {
                    if ((int)$sId !== (int)$user->id) {
                        $reviewer = User::query()->find($sId);
                        if ($reviewer) {
                            $reviewer->notify(new CollegeNotification(
                                'Memo Signature Request',
                                "{$user->name} has requested your signature on: '{$memo->title}'",
                                route('admin.memos.show', $memo->id)
                            ));
                        }
                    }
                }
            } elseif ($status === 'sent') {
                $this->notifyRecipients($memo);
            }
        });

        CollegeFlash::forNextRequestToo('status', __('Memo created successfully.'));
        $this->closeCreate();
        $this->redirect(route('admin.memos.index'), navigate: true);
    }

    private function notifyRecipients(Memo $memo): void
    {
        $senderName = $memo->sender_name;
        $title = 'New Memo: ' . $memo->title;
        $message = "You have received a new memo from {$senderName}.";
        $url = route('admin.memos.show', $memo->id);

        if ($memo->recipient_type === 'user' && $memo->recipient_entity_id) {
            $recipient = User::query()->find($memo->recipient_entity_id);
            if ($recipient) {
                $recipient->notify(new CollegeNotification($title, $message, $url));
            }
        } elseif ($memo->recipient_type === 'department' && $memo->recipient_entity_id) {
            // Find all active admins/staff/teachers in this department
            $users = User::query()->where('active', true)
                ->where(function ($q) use ($memo) {
                    $q->whereHas('admin', fn($a) => $a->where('department_id', $memo->recipient_entity_id))
                      ->orWhereHas('teacher', fn($t) => $t->where('department_id', $memo->recipient_entity_id));
                })->get();
            foreach ($users as $u) {
                $u->notify(new CollegeNotification($title, $message, $url));
            }
        } elseif ($memo->recipient_type === 'faculty' && $memo->recipient_entity_id) {
            $users = User::query()->where('active', true)
                ->where(function ($q) use ($memo) {
                    $q->whereHas('admin', fn($a) => $a->where('faculty_id', $memo->recipient_entity_id));
                })->get();
            foreach ($users as $u) {
                $u->notify(new CollegeNotification($title, $message, $url));
            }
        } elseif ($memo->recipient_type === 'role' && $memo->recipient_role_id) {
            $users = User::query()->where('active', true)
                ->whereHas('admin', fn($a) => $a->where('type', $memo->recipient_role_id))
                ->get();
            foreach ($users as $u) {
                $u->notify(new CollegeNotification($title, $message, $url));
            }
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        // Fetch preferences
        $memos_require_signature = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_require_signature')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $memos_multiple_signatories = filter_var(
            \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_multiple_signatories')->value('setting_value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        // 1. Fetching Memos based on active tab
        $query = Memo::query()->with(['sender', 'signingUser', 'recipientRole'])->orderBy('updated_at', 'desc');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->confidentiality !== 'all') {
            $query->where('confidentiality_level', $this->confidentiality);
        }

        switch ($this->activeTab) {
            case 'inbox':
                $query->whereIn('status', ['sent', 'archived']);
                if (!$user->isAdminOwner() && $user->adminRoleSlug() !== 'system_admin' && !$user->hasAdminPermission('view_all_memos')) {
                    $query->whereHas('readReceipts', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                }
                break;
            case 'outbox':
                $query->where('sender_id', $user->id)
                    ->whereIn('status', ['sent', 'archived']);
                break;
            case 'pending':
                $query->where('status', 'pending_signature')
                    ->where(function ($q) use ($user) {
                        $q->where('signing_user_id', $user->id)
                          ->orWhereHas('signatories', function ($sq) use ($user) {
                              $sq->where('user_id', $user->id)->where('status', 'pending');
                          });
                    });
                break;
            case 'drafts':
                $query->where('sender_id', $user->id)
                    ->where('status', 'draft');
                break;
        }

        $allMemos = $query->get();

        // Filter memos by permission logic (canBeViewedBy)
        $memos = $allMemos->filter(fn($memo) => $memo->canBeViewedBy($user));

        // Options for recipient dropdowns
        $departments = Department::query()->orderBy('name')->get();
        $faculties = Faculty::query()->orderBy('name')->get();
        $roles = UserRole::query()->orderBy('display_name')->get();
        
        // Users for direct recipient or signature routing (admins/staff)
        $users = User::query()
            ->where('active', true)
            ->where('type', 'admin')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        return view('livewire.admin.memos.memo-index-page', [
            'memos' => $memos,
            'departments' => $departments,
            'faculties' => $faculties,
            'roles' => $roles,
            'users' => $users,
            'memos_require_signature' => $memos_require_signature,
            'memos_multiple_signatories' => $memos_multiple_signatories,
        ])->layout('components.layouts.admin', [
            'title' => __('Memos'),
            'headerTitle' => __('Administrative Memos'),
            'headerDescription' => __('Draft, approve, and track memos moving between departments and faculties.'),
        ]);
    }
}
