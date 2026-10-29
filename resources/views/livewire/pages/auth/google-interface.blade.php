<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function mount(): void
    {
        $this->member();
    }

    public function chooseInterface(string $interface): void
    {
        abort_unless(in_array($interface, ['renter', 'owner'], true), 403);
        $user = $this->member();
        AccountLockout::ensureNotLocked($user->email, 'interface');

        Session::forget(['google_oauth_pending_user', 'url.intended']);
        Session::put('active_interface', $interface);

        $this->redirect(route($user->hasVerifiedIdentity() ? $interface.'.dashboard' : 'identity.verify'));
    }

    public function cancelInterfaceSelection(Logout $logout): void
    {
        $this->member();
        $logout();
        $this->redirect(route('login'));
    }

    private function member(): User
    {
        $user = User::find(auth()->id());
        abort_unless($user && ! $user->isSuspended() && ! $user->isAdmin() && $user->hasVerifiedEmail()
            && Session::get('google_oauth_pending_user') === $user->id, 403);

        return $user;
    }
}; ?>

<div>
    <h1 class="text-3xl font-extrabold tracking-tight text-[#071a3d]">Google sign-in complete.</h1>
    <p class="mt-3 text-sm leading-6 text-slate-500">Signed in as {{ auth()->user()->email }}. Choose how you would like to use Lendly.</p>

    <x-interface-picker :show="true" />
</div>
