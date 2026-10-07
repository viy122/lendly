<?php

namespace Tests\Feature;

use App\Enums\RentalAdminActionType;
use App\Enums\RentalStatus;
use App\Livewire\Admin\Payments\Settings;
use App\Livewire\Admin\Rentals\Index as AdminRentalsIndex;
use App\Livewire\Admin\Rentals\Show as AdminShow;
use App\Livewire\Renter\Rentals\Show as RenterShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\OfflinePaymentSetting;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\Rental;
use App\Models\RentalAdminAction;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalAdministration;
use App\Services\RentalAgreement;
use App\Services\RentalPayments;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\Concerns\CreatesPaymentProof;
use Tests\TestCase;

class OfflinePaymentTest extends TestCase
{
    use CreatesPaymentProof;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::set('local', Storage::fake('offline-payments-'.Str::random(12)));
        OfflinePaymentSetting::create(['id' => 1, 'method' => 'bank_transfer', 'instructions' => 'Test bank and recipient instructions', 'enabled' => true]);
    }

    private function rental(): Rental
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::firstOrCreate(['slug' => 'offline-payment'], ['name' => 'Tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Offline payment item',
            'description' => 'Item', 'condition' => 'good', 'price_per_day' => 100,
            'security_deposit' => 500, 'location' => 'Manila', 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true,
        ]);
        $terms = [
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(5), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830,
        ];
        $request = RentalRequest::create($terms + ['status' => 'approved', 'renter_terms_accepted_at' => now(), 'owner_terms_accepted_at' => now()]);
        $request->update(['agreement_terms' => RentalAgreement::termsFor($request)]);

        return Rental::create($terms + ['rental_request_id' => $request->id, 'owner_id' => $owner->id]);
    }

    private function submit(Rental $rental, string $reference = 'BANK-REFERENCE'): PaymentSubmission
    {
        return RentalPayments::submit($rental, $rental->renter, $reference, $this->paymentProof());
    }

    public function test_submission_is_private_and_does_not_complete_payment_or_unlock_handover(): void
    {
        $rental = $this->rental();
        $admin = User::factory()->admin()->create();
        $submission = $this->submit($rental, ' bank-reference ');
        $this->assertSame('BANK-REFERENCE', $submission->external_reference);
        $this->assertSame('830.00', $submission->amount);
        Storage::disk('local')->assertExists($submission->proof_path);
        $this->assertSame(RentalStatus::PaymentPending, $rental->fresh()->status);
        $this->assertNull($rental->fresh()->paid_at);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('security_deposits', 0);
        $this->assertFalse($rental->fresh()->awaitingPickupConfirmation());
        $this->assertCount(1, $admin->notifications()->where('data->type', 'payment_submitted')->get());
        $this->assertCount(0, $rental->owner->notifications);
        $this->actingAs($rental->renter)->get(route('renter.rentals.show', $rental))->assertOk()
            ->assertSee('awaiting verification')->assertDontSee('Transaction receipt')->assertDontSee('simulated');
        $this->get(route('payment-proofs.show', $submission))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($admin)->get(route('payment-proofs.show', $submission))->assertOk();
        foreach ([$rental->owner, User::factory()->create(), User::factory()->admin()->unverified()->create()] as $user) {
            $this->actingAs($user)->get(route('payment-proofs.show', $submission))->assertForbidden();
        }
        $this->actingAs(User::factory()->admin()->create(['status' => 'suspended']))
            ->get(route('payment-proofs.show', $submission))->assertRedirect(route('login'));
        auth()->logout();
        $this->get(route('payment-proofs.show', $submission))->assertRedirect(route('login'));
    }

    public function test_admin_must_confirm_received_funds_and_verification_is_audited_once(): void
    {
        $rental = $this->rental();
        $submission = $this->submit($rental);
        $admin = User::factory()->admin()->create();
        $form = Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Matched reference and total in bank records');
        $form->call('verifyPayment', $submission->id)->assertHasErrors(['funds_received']);
        $this->assertDatabaseCount('payments', 0);
        $form->set('funds_received', true)->call('verifyPayment', $submission->id)->assertHasNoErrors();
        $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        $payment = $rental->fresh()->payment;
        $this->assertSame($submission->id, $payment->payment_submission_id);
        $this->assertSame($admin->id, $payment->verified_by);
        $this->assertSame('bank_transfer', $payment->method);
        $this->assertSame('BANK-REFERENCE', $payment->external_reference);
        $this->assertTrue($payment->paid_at->equalTo($rental->fresh()->paid_at));
        $this->assertSame('held', $rental->fresh()->securityDeposit->status->value);
        $this->assertSame('verified', $submission->fresh()->status);
        $this->assertSame(RentalAdminActionType::PaymentVerified, RentalAdminAction::sole()->action);
        $this->assertSame('payment_pending', RentalAdminAction::sole()->before_state['status']);
        $this->assertSame('paid', RentalAdminAction::sole()->after_state['status']);
        foreach ([$rental->owner, $rental->renter] as $party) {
            $this->assertCount(1, $party->notifications()->where('data->type', 'payment_confirmed')->get());
        }
        $form->set('reason', 'Repeat')->set('funds_received', true)->call('verifyPayment', $submission->id)->assertForbidden();
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('security_deposits', 1);
        $this->assertDatabaseCount('rental_admin_actions', 1);
    }

    public function test_rejection_preserves_old_proof_and_allows_a_corrected_submission(): void
    {
        $rental = $this->rental();
        $first = $this->submit($rental);
        $admin = User::factory()->admin()->create();
        RentalAdministration::perform($rental, $admin, RentalAdminActionType::PaymentRejected, 'Receipt is unreadable; check before paying again', $first->id);
        $this->assertSame('rejected', $first->fresh()->status);
        $this->assertSame($admin->id, $first->fresh()->reviewed_by);
        $this->assertNull($rental->fresh()->paid_at);
        $this->assertDatabaseCount('payments', 0);
        foreach ([$rental->owner, $rental->renter] as $party) {
            $this->assertCount(1, $party->notifications()->where('data->type', 'payment_rejected')->get());
        }
        $second = $this->submit($rental);
        $this->assertNotSame($first->id, $second->id);
        Storage::disk('local')->assertExists($first->proof_path);
        $this->actingAs($rental->renter)->get(route('renter.rentals.show', $rental))->assertOk()->assertSee('Receipt is unreadable');
        RentalAdministration::perform($rental, $admin, RentalAdminActionType::PaymentVerified, 'Corrected proof matches receipt of total', $second->id, true);
        $this->assertSame($second->id, $rental->fresh()->payment->payment_submission_id);
    }

    public function test_only_admins_can_review_and_proof_cannot_be_applied_to_another_transaction(): void
    {
        $rental = $this->rental();
        $submission = $this->submit($rental);
        foreach ([$rental->owner, $rental->renter, User::factory()->create()] as $actor) {
            foreach ([RentalAdminActionType::PaymentVerified, RentalAdminActionType::PaymentRejected] as $action) {
                try {
                    RentalAdministration::perform($rental, $actor, $action, 'Unauthorized', $submission->id, true);
                    $this->fail('Unauthorized payment review must be blocked.');
                } catch (AuthorizationException) {
                    $this->assertSame('pending', $submission->fresh()->status);
                }
            }
        }
        $other = $this->rental();
        $this->submit($other, 'OTHER-REFERENCE');
        Livewire::actingAs(User::factory()->admin()->create())->test(AdminShow::class, ['rental' => $other])
            ->set('reason', 'Wrong transaction')->set('funds_received', true)->call('verifyPayment', $submission->id)->assertNotFound();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_missing_agreement_acceptance_and_changed_booking_state_block_payment(): void
    {
        foreach (['owner_terms_accepted_at', 'renter_terms_accepted_at', 'agreement_terms', 'status'] as $field) {
            $rental = $this->rental();
            $rental->rentalRequest->update([$field => $field === 'status' ? 'rejected' : null]);
            Livewire::actingAs($rental->renter)->test(RenterShow::class, ['rental' => $rental])->call('submitPaymentProof')->assertForbidden();
        }
        $rental = $this->rental();
        $submission = $this->submit($rental);
        $admin = User::factory()->admin()->create();
        $form = Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Stale pending form')->set('funds_received', true);
        Rental::whereKey($rental->id)->update(['status' => RentalStatus::Cancelled]);
        $form->call('verifyPayment', $submission->id)->assertForbidden();
        RentalAdministration::perform($rental, $admin, RentalAdminActionType::PaymentRejected, 'Cancelled; offline refund handled if required', $submission->id);
        $this->assertSame(RentalStatus::Cancelled, $rental->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_proof_and_reference_are_required_and_pending_submissions_cannot_be_duplicated(): void
    {
        $rental = $this->rental();
        $form = Livewire::actingAs($rental->renter)->test(RenterShow::class, ['rental' => $rental]);
        $form->call('submitPaymentProof')->assertHasErrors(['payment_reference', 'payment_proof']);
        $form->set('payment_reference', 'INVALID REFERENCE')->set('payment_proof', UploadedFile::fake()->create('proof.txt', 1, 'text/plain'))
            ->call('submitPaymentProof')->assertHasErrors(['payment_reference', 'payment_proof']);
        $form->set('payment_reference', 'VALID-REFERENCE')->set('payment_proof', UploadedFile::fake()->create('proof.pdf', 5121, 'application/pdf'))
            ->call('submitPaymentProof')->assertHasErrors(['payment_proof']);
        $this->assertDatabaseCount('payment_submissions', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles('payment-proofs'));
        $this->submit($rental);
        $form->call('submitPaymentProof')->assertForbidden();
        $this->assertDatabaseCount('payment_submissions', 1);
    }

    public function test_used_references_and_changed_amounts_are_blocked_and_instructions_are_snapshotted(): void
    {
        $rental = $this->rental();
        $submission = $this->submit($rental);
        $settings = OfflinePaymentSetting::current();
        $settings->update(['instructions' => 'Changed collection instructions', 'enabled' => false]);
        $this->assertSame('Test bank and recipient instructions', $submission->fresh()->instructions);
        $admin = User::factory()->admin()->create();
        $rental->update(['total_amount' => 900]);
        Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Amount mismatch')->set('funds_received', true)->call('verifyPayment', $submission->id)->assertHasErrors(['reason']);
        $rental->update(['total_amount' => 830]);
        RentalAdministration::perform($rental, $admin, RentalAdminActionType::PaymentVerified, 'Full original amount received', $submission->id, true);
        $settings->update(['enabled' => true]);
        $other = $this->rental();
        Livewire::actingAs($other->renter)->test(RenterShow::class, ['rental' => $other])
            ->set('payment_reference', 'bank-reference')->set('payment_proof', $this->paymentProof())
            ->call('submitPaymentProof')->assertHasErrors(['payment_reference']);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_submissions', 1);
    }

    public function test_missing_proof_or_audit_failure_cannot_complete_payment(): void
    {
        $rental = $this->rental();
        $submission = $this->submit($rental);
        $admin = User::factory()->admin()->create();
        Storage::disk('local')->delete($submission->proof_path);
        Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Missing proof')->set('funds_received', true)->call('verifyPayment', $submission->id)->assertHasErrors(['reason']);
        Storage::disk('local')->put($submission->proof_path, 'Restored test evidence');
        RentalAdminAction::creating(fn () => throw new RuntimeException('Audit failure'));
        try {
            RentalAdministration::perform($rental, $admin, RentalAdminActionType::PaymentVerified, 'Verified collection', $submission->id, true);
            $this->fail('The audit must be atomic with verification.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit failure', $exception->getMessage());
        } finally {
            RentalAdminAction::flushEventListeners();
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('security_deposits', 0);
        $this->assertDatabaseCount('rental_admin_actions', 0);
        $this->assertSame('pending', $submission->fresh()->status);
        $this->assertSame(RentalStatus::PaymentPending, $rental->fresh()->status);
        $this->assertNull($rental->fresh()->paid_at);
        $this->assertCount(0, $rental->renter->notifications);
    }

    public function test_payment_settings_require_admin_permission_and_real_instructions_before_submission(): void
    {
        $rental = $this->rental();
        Livewire::actingAs($rental->renter)->test(Settings::class)->assertForbidden();
        OfflinePaymentSetting::current()->delete();
        $form = Livewire::actingAs($rental->renter)->test(RenterShow::class, ['rental' => $rental])
            ->set('payment_reference', 'RECEIPT-123')->set('payment_proof', $this->paymentProof());
        $form->call('submitPaymentProof')->assertHasErrors(['payment_proof']);
        $admin = User::factory()->admin()->create();
        $settingsForm = Livewire::actingAs($admin)->test(Settings::class)->set('enabled', true);
        $settingsForm->call('save')->assertHasErrors(['instructions']);
        $settingsForm->set('method', 'cash')->set('instructions', '  Test collection office; obtain official receipt  ')->call('save')->assertHasNoErrors();
        $this->assertSame($admin->id, OfflinePaymentSetting::current()->updated_by);
        $this->assertSame('Test collection office; obtain official receipt', OfflinePaymentSetting::current()->instructions);
        Livewire::actingAs($rental->renter)->test(RenterShow::class, ['rental' => $rental])
            ->set('payment_reference', 'RECEIPT-123')->set('payment_proof', $this->paymentProof())
            ->call('submitPaymentProof')->assertHasNoErrors();
        $this->assertSame('cash', PaymentSubmission::sole()->method);
        RentalAdministration::perform($rental, $admin, RentalAdminActionType::PaymentVerified, 'Cash receipt and full amount checked', PaymentSubmission::sole()->id, true);
        $this->assertSame('cash', Payment::sole()->method);
    }

    public function test_admin_can_configure_payment_instructions_in_the_transactions_modal(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Settings::class, ['modal' => true])->assertForbidden();

        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(AdminRentalsIndex::class)
            ->assertSeeLivewire(Settings::class)
            ->set('search', 'Camera')->set('status', 'paid')
            ->assertSet('search', 'Camera')->assertSet('status', 'paid')
            ->assertSeeHtml('aria-controls="offline-payment-modal"')
            ->assertDontSeeHtml('href="'.route('admin.payments.settings').'"');

        $form = Livewire::test(Settings::class, ['modal' => true])
            ->assertSet('modal', true)
            ->assertSet('method', 'bank_transfer')
            ->assertSet('instructions', 'Test bank and recipient instructions')
            ->assertSet('enabled', true)
            ->assertSee('Close payment settings')
            ->set('instructions', '   ')->call('save')->assertHasErrors(['instructions']);

        $form->set('method', 'cash')->set('instructions', '  Collect at the office with an official receipt  ')
            ->set('enabled', false)->call('save')->assertHasNoErrors()->assertNoRedirect()
            ->assertSee('Payment instructions saved.');

        $this->assertDatabaseHas('offline_payment_settings', [
            'id' => 1, 'method' => 'cash', 'instructions' => 'Collect at the office with an official receipt',
            'enabled' => false, 'updated_by' => $admin->id,
        ]);
    }

    public function test_pdf_proof_is_supported_and_pending_references_cannot_be_reused_for_another_booking(): void
    {
        $rental = $this->rental();
        $pdf = UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $submission = RentalPayments::submit($rental, $rental->renter, 'PDF-REFERENCE', $pdf);
        $this->assertStringEndsWith('.pdf', $submission->proof_path);
        $other = $this->rental();
        Livewire::actingAs($other->renter)->test(RenterShow::class, ['rental' => $other])
            ->set('payment_reference', 'pdf-reference')->set('payment_proof', $this->paymentProof())
            ->call('submitPaymentProof')->assertHasErrors(['payment_reference']);
        $this->assertDatabaseCount('payment_submissions', 1);
    }

    public function test_failed_submission_cleans_up_private_proof_without_creating_payment_records(): void
    {
        $rental = $this->rental();
        PaymentSubmission::creating(fn () => throw new RuntimeException('Submission storage failure'));
        try {
            $this->submit($rental);
            $this->fail('The submission must fail with its storage operation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Submission storage failure', $exception->getMessage());
        } finally {
            PaymentSubmission::flushEventListeners();
        }
        $this->assertDatabaseCount('payment_submissions', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('security_deposits', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles('payment-proofs'));
    }

    public function test_verified_payment_and_proof_survive_participant_and_verifier_deletion(): void
    {
        $rental = $this->rental();
        $submission = $this->submit($rental);
        $admin = User::factory()->admin()->create();
        RentalAdministration::perform($rental, $admin, RentalAdminActionType::PaymentVerified, 'Full amount matched in collection records', $submission->id, true);
        $payment = $rental->fresh()->payment->getAttributes();
        $rental->owner->delete();
        $rental->renter->delete();
        $admin->delete();
        $viewer = User::factory()->admin()->create();
        $this->actingAs($viewer)->get(route('admin.rentals.show', $rental))->assertOk()
            ->assertSee('Offline payment verified')->assertSee('BANK-REFERENCE')->assertSee('Deleted account');
        $this->get(route('payment-proofs.show', $submission))->assertOk();
        $this->assertSame($payment, $rental->fresh()->payment->getAttributes());
        $this->assertTrue($submission->fresh()->reviewer->trashed());
        Storage::disk('local')->assertExists($submission->proof_path);
    }
}
