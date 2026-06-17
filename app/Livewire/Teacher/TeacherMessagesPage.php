<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SchoolLicenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeacherMessagesPage extends Component
{
    use WithFileUploads;

    // Licensing
    public bool $isLicensed = false;
    public array $pricingDetails = [];

    // Conversation State
    public ?int $activeConversationId = null;
    public string $messageBody = '';
    public $attachment = null;
    public int $perPage = 20;
    public bool $hasMoreMessages = false;

    // Directory Search / Start Chat
    public string $searchQuery = '';
    public bool $showNewChatModal = false;

    // Mobile View Toggle
    public bool $mobileShowChat = false;

    protected $queryString = [
        'activeConversationId' => ['except' => null, 'as' => 'chat'],
    ];

    public function mount(SchoolLicenceService $licenceService): void
    {
        $this->isLicensed = $licenceService->can('messaging');

        if (!$this->isLicensed) {
            $row = $licenceService->getLicenceRow();
            $maxStudents = (int) ($row['max_active_students'] ?? 0);
            $band = 'tier_1';
            foreach (config('licence.student_pricing_bands', []) as $key => $b) {
                if ($maxStudents >= $b['min'] && ($b['max'] === null || $maxStudents <= $b['max'])) {
                    $band = $key;
                    break;
                }
            }
            $this->pricingDetails = $licenceService->modulePrice('messaging', $band);
        }

        if ($this->activeConversationId) {
            $this->selectConversation($this->activeConversationId);
        }
    }

    public function selectConversation(int $id): void
    {
        $this->activeConversationId = $id;
        $this->mobileShowChat = true;
        $this->perPage = 20;
        $this->attachment = null;

        $convo = Conversation::find($id);
        if ($convo) {
            // Mark as read
            $convo->participants()->updateExistingPivot(auth()->id(), [
                'last_read_at' => now(),
            ]);
        }

        $this->dispatch('scroll-to-bottom');
    }

    public function loadMoreMessages(): void
    {
        if (!$this->isLicensed || !$this->activeConversationId) {
            return;
        }

        $total = Message::where('conversation_id', $this->activeConversationId)->count();
        if ($this->perPage < $total) {
            $this->perPage += 20;
        }
        $this->hasMoreMessages = $this->perPage < $total;
    }

    public function closeChat(): void
    {
        $this->mobileShowChat = false;
    }

    public function sendMessage(): void
    {
        if (!$this->isLicensed) {
            return;
        }

        $this->validate([
            'messageBody' => [$this->attachment ? 'nullable' : 'required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if (!$this->activeConversationId) {
            return;
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentSize = null;

        if ($this->attachment) {
            $attachmentName = $this->attachment->getClientOriginalName();
            $attachmentSize = $this->attachment->getSize();
            $attachmentPath = $this->attachment->store('chat_attachments');
        }

        $message = Message::create([
            'conversation_id' => $this->activeConversationId,
            'sender_id' => auth()->id(),
            'body' => $this->messageBody ?: null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_size' => $attachmentSize,
        ]);

        $previewText = $this->messageBody
            ? Str::limit($this->messageBody, 100)
            : '📎 ' . $attachmentName;

        $message->conversation->update([
            'last_message_at' => now(),
            'last_message_text' => $previewText,
        ]);

        // Keep current user pivot updated
        $message->conversation->participants()->updateExistingPivot(auth()->id(), [
            'last_read_at' => now(),
        ]);

        $this->messageBody = '';
        $this->attachment = null;
        $this->dispatch('scroll-to-bottom');
    }

    public function startNewChat(int $userId): void
    {
        if (!$this->isLicensed) {
            return;
        }

        $currentUserId = auth()->id();

        // Check if conversation already exists
        $existing = Conversation::query()
            ->whereHas('participants', function ($q) use ($currentUserId) {
                $q->where('users.id', $currentUserId);
            })
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->first();

        if ($existing) {
            $this->selectConversation((int) $existing->id);
        } else {
            $convo = Conversation::create([
                'last_message_at' => now(),
                'last_message_text' => __('Conversation started'),
            ]);
            $convo->participants()->attach([$currentUserId, $userId]);
            $this->selectConversation((int) $convo->id);
        }

        $this->searchQuery = '';
        $this->showNewChatModal = false;
    }

    public function render(): View
    {
        $currentUserId = auth()->id();

        // Fetch user's conversation list
        $conversations = $this->isLicensed
            ? Conversation::query()
                ->whereHas('participants', function ($q) use ($currentUserId) {
                    $q->where('users.id', $currentUserId);
                })
                ->with(['participants', 'messages'])
                ->orderByDesc('last_message_at')
                ->get()
            : collect();

        // Fetch active messages
        $messages = collect();
        $activeRecipient = null;
        if ($this->isLicensed && $this->activeConversationId) {
            $activeConvo = Conversation::with(['participants'])->find($this->activeConversationId);
            if ($activeConvo) {
                $totalMessages = Message::where('conversation_id', $this->activeConversationId)->count();
                $this->hasMoreMessages = $this->perPage < $totalMessages;

                $messages = Message::where('conversation_id', $this->activeConversationId)
                    ->with('sender')
                    ->orderBy('id', 'desc')
                    ->take($this->perPage)
                    ->get()
                    ->reverse();

                $activeRecipient = $activeConvo->getOtherParticipant($currentUserId);
            }
        }

        // Search directory
        $searchResults = collect();
        if ($this->searchQuery !== '') {
            $searchResults = User::query()
                ->where('id', '!=', $currentUserId)
                ->where('name', 'like', '%' . $this->searchQuery . '%')
                ->limit(6)
                ->get();
        }

        return view('livewire.teacher.teacher-messages-page', [
            'conversations' => $conversations,
            'messages' => $messages,
            'activeRecipient' => $activeRecipient,
            'searchResults' => $searchResults,
        ])->layout('components.layouts.teacher', [
            'title' => __('Messages Hub'),
            'headerDescription' => __('Real-time collaboration and secure support messaging.'),
        ]);
    }
}
