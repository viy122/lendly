<?php

namespace App\Livewire\Messages;

use App\Livewire\Messages\Concerns\ListsThreads;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use ListsThreads;

    public function render(): View
    {
        return view('livewire.messages.index', [
            'threads' => $this->threadsForCurrentUser(),
        ]);
    }
}
