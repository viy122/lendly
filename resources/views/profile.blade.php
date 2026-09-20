<x-app-layout>
    <x-page-header eyebrow="Account" title="Profile" subtitle="Manage your account information, password, and data." maxWidth="max-w-4xl" />

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="rounded-xl border border-rose-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
