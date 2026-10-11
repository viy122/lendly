<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Bell extends Component
{
    public bool $open = false;

    #[On('notifications-refresh')]
    public function refresh(): void
    {
        // no-op; just forces a re-render since the listener attribute below drives it
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(string $notificationId): void
    {
        auth()->user()->notificationsForActiveInterface()->where('id', $notificationId)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->notificationsForActiveInterface()->whereNull('read_at')->get()->markAsRead();
    }

    public function render(): View
    {
        return view('livewire.notifications.bell', [
            'notifications' => auth()->user()->notificationsForActiveInterface()->latest()->take(8)->get(),
            'unreadCount' => auth()->user()->notificationsForActiveInterface()->whereNull('read_at')->count(),
        ]);
    }
}
