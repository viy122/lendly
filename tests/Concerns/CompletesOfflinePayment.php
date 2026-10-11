<?php

namespace Tests\Concerns;

use App\Livewire\Admin\Rentals\Show as AdminShow;
use App\Livewire\Renter\Rentals\Show as RenterShow;
use App\Models\OfflinePaymentSetting;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

trait CompletesOfflinePayment
{
    use CreatesPaymentProof;

    private function completeOfflinePayment(Rental $rental): void
    {
        Storage::set('local', Storage::fake('offline-payments-'.Str::random(12)));
        OfflinePaymentSetting::updateOrCreate(['id' => 1], [
            'method' => 'bank_transfer', 'instructions' => 'Test collection instructions', 'enabled' => true,
        ]);
        Livewire::actingAs($rental->renter)->test(RenterShow::class, ['rental' => $rental])
            ->set('payment_reference', 'TRANSFER-'.$rental->id)
            ->set('payment_proof', $this->paymentProof())
            ->call('submitPaymentProof')->assertHasNoErrors();
        $submission = $rental->paymentSubmissions()->first();
        $verification = Livewire::actingAs(User::factory()->admin()->create())->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Matched reference and full amount in the collection records')
            ->set('funds_received', true)->call('verifyPayment', $submission->id);
        $this->assertSame([], $verification->errors()->messages());
        Livewire::actingAs($rental->renter);
    }
}
