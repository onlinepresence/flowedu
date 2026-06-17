<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public function getNotificationsProperty()
    {
        return auth()->user()
            ? auth()->user()->unreadNotifications()->latest()->take(10)->get()
            : collect();
    }

    public function getUnreadCountProperty(): int
    {
        return auth()->user()
            ? auth()->user()->unreadNotifications()->count()
            : 0;
    }

    public function markAsRead(string $id): void
    {
        $user = auth()->user();
        if ($user) {
            $notification = $user->unreadNotifications()->find($id);
            if ($notification) {
                $notification->markAsRead();
                
                $data = $notification->data;
                if (isset($data['action_url'])) {
                    $this->redirect($data['action_url'], navigate: true);
                }
            }
        }
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
    }

    public function render(): View
    {
        return view('livewire.navigation.notification-dropdown');
    }
}
