<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

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
        $notifications = auth()->user()->notificationsForActiveInterface()->paginate(15);

        return view('livewire.notifications.index', ['notifications' => $notifications]);
    }
}
