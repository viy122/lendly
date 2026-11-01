<?php

namespace App\Livewire\Notifications;

use App\Enums\NotificationType;
use App\Livewire\Concerns\FiltersRecords;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use FiltersRecords;
    use WithPagination;

    public string $filter = 'all';

    public string $typeFilter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
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
        $query = auth()->user()->notificationsForActiveInterface()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when($this->typeFilter !== 'all', fn ($query) => $query->whereIn('data->type', collect(NotificationType::cases())->filter(fn ($type) => $type->category() === $this->typeFilter)->map(fn ($type) => $type->value)->all()));
        $notifications = $this->filterRecords($query->getQuery(), ['data'])->paginate(15);

        return view('livewire.notifications.index', ['notifications' => $notifications]);
    }
}
