<?php

use App\Livewire\Admin\DamageReports\Index as AdminDamageReportsIndex;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Disputes\Index as AdminDisputesIndex;
use App\Livewire\Admin\Listings\Index as AdminListingsIndex;
use App\Livewire\Admin\Rentals\Index as AdminRentalsIndex;
use App\Livewire\Admin\Rentals\Show as AdminRentalShow;
use App\Livewire\Listings\Browse as ListingsBrowse;
use App\Livewire\Listings\Map as ListingsMap;
use App\Livewire\Listings\Show as ListingsShow;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Livewire\Messages\Index as MessagesIndex;
use App\Livewire\Messages\ListingShow as ListingMessagesShow;
use App\Livewire\Messages\Show as MessagesShow;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Owner\Listings\Form as OwnerListingForm;
use App\Livewire\Owner\Listings\Index as OwnerListingsIndex;
use App\Livewire\Owner\RentalRequests\Index as OwnerRentalRequestsIndex;
use App\Livewire\Owner\Rentals\Index as OwnerRentalsIndex;
use App\Livewire\Owner\Rentals\Show as OwnerRentalsShow;
use App\Livewire\Profiles\Show as PublicProfile;
use App\Livewire\RentalRequests\Create as RentalRequestCreate;
use App\Livewire\Renter\RentalRequests\Index as RenterRentalRequestsIndex;
use App\Livewire\Renter\Rentals\Index as RenterRentalsIndex;
use App\Livewire\Renter\Rentals\Show as RenterRentalsShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', MemberDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', NotificationsIndex::class)->name('notifications.index');
    Route::get('messages', MessagesIndex::class)->name('messages.index');
    Route::get('listings/{listing}/contact', ListingMessagesShow::class)->middleware('role:renter')->name('listings.contact');
    Route::get('messages/listings/{conversation}', ListingMessagesShow::class)->name('messages.listing');
    Route::get('rental-requests/{rentalRequest}/chat', MessagesShow::class)->name('rental-requests.chat');
});

Route::get('listings', ListingsBrowse::class)->name('listings.index');
Route::get('map', ListingsMap::class)->name('map');
Route::get('listings/{listing}', ListingsShow::class)->name('listings.show');
Route::get('users/{user}', PublicProfile::class)->name('users.show');

Route::middleware(['auth', 'verified', 'role:renter'])->prefix('renter')->name('renter.')->group(function () {
    Route::get('listings/{listing}/request', RentalRequestCreate::class)->name('rental-requests.create');
    Route::get('rental-requests', RenterRentalRequestsIndex::class)->name('rental-requests.index');

    Route::get('rentals', RenterRentalsIndex::class)->name('rentals.index');
    Route::get('rentals/{rental}', RenterRentalsShow::class)->name('rentals.show');
});

Route::middleware(['auth', 'verified', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('listings', OwnerListingsIndex::class)->name('listings.index');
    Route::get('listings/create', OwnerListingForm::class)->name('listings.create');
    Route::get('listings/{listing}/edit', OwnerListingForm::class)->name('listings.edit');

    Route::get('rental-requests', OwnerRentalRequestsIndex::class)->name('rental-requests.index');
    Route::get('rentals', OwnerRentalsIndex::class)->name('rentals.index');
    Route::get('rentals/{rental}', OwnerRentalsShow::class)->name('rentals.show');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('listings', AdminListingsIndex::class)->name('listings.index');
    Route::get('rentals', AdminRentalsIndex::class)->name('rentals.index');
    Route::get('rentals/{rental}', AdminRentalShow::class)->name('rentals.show');
    Route::get('damage-reports', AdminDamageReportsIndex::class)->name('damage-reports.index');
    Route::get('disputes', AdminDisputesIndex::class)->name('disputes.index');
});

require __DIR__.'/auth.php';
