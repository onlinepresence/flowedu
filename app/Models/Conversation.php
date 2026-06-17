<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'last_message_at',
        'last_message_text',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Helper to get the other participant in a peer-to-peer chat.
     */
    public function getOtherParticipant(int $currentUserId): ?User
    {
        return $this->participants->first(fn ($u) => $u->id !== $currentUserId);
    }

    /**
     * Checks if there is any unread message in this conversation for a specific user.
     */
    public function hasUnreadMessages(int $userId): bool
    {
        $participant = $this->participants->first(fn ($u) => $u->id === $userId);
        if (!$participant) {
            return false;
        }

        $lastRead = $participant->pivot->last_read_at;
        if (!$lastRead) {
            return true;
        }

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('created_at', '>', $lastRead)
            ->exists();
    }
}
