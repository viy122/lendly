<?php

namespace Tests\Feature;

use App\Enums\DamageReportStatus;
use App\Enums\DisputeResolution;
use App\Enums\RentalAdminActionType;
use App\Enums\RentalStatus;
use App\Enums\SecurityDepositStatus;
use App\Livewire\Admin\Disputes\Index as AdminDisputes;
use App\Livewire\Admin\Rentals\Index as AdminIndex;
use App\Livewire\Admin\Rentals\Show as AdminShow;
use App\Models\Category;
use App\Models\ConditionRecord;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalAdminAction;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Services\RentalAdministration;
use App\Services\RentalAgreement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AdminRentalManagementTest extends TestCase
{
    use RefreshDatabase;

    private function rental(RentalStatus $status = RentalStatus::Paid, int $startInDays = 3): Rental
    {
        $owner = User::factory()->create(['name' => 'Transaction Owner']);
        $renter = User::factory()->create(['name' => 'Transaction Renter']);
        $category = Category::firstOrCreate(['slug' => 'admin-management'], ['name' => 'Admin Management']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Management item',
            'description' => 'Item details', 'condition' => 'good', 'price_per_day' => 100,
            'security_deposit' => 500, 'location' => 'Manila', 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true,
        ]);
        $terms = [
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays($startInDays)->startOfDay(),
            'end_date' => now()->addDays($startInDays + 2)->startOfDay(), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830,
        ];
        $request = RentalRequest::create($terms + ['status' => 'approved', 'renter_terms_accepted_at' => now(), 'owner_terms_accepted_at' => now()]);
        $request->update(['agreement_terms' => RentalAgreement::termsFor($request)]);
        $handedOver = in_array($status, [RentalStatus::Active, RentalStatus::Overdue, RentalStatus::Returned, RentalStatus::Completed], true);
        $returned = in_array($status, [RentalStatus::Returned, RentalStatus::Completed], true);
        $rental = Rental::create($terms + [
            'rental_request_id' => $request->id, 'owner_id' => $owner->id, 'status' => $status,
            'paid_at' => $status === RentalStatus::PaymentPending ? null : now(),
            'pickup_confirmed_by_owner_at' => $handedOver ? now() : null,
            'pickup_confirmed_by_renter_at' => $handedOver ? now() : null,
            'return_confirmed_by_owner_at' => $returned ? now() : null,
            'return_confirmed_by_renter_at' => $returned ? now() : null,
            'completed_at' => $status === RentalStatus::Completed ? now() : null,
            'archived_at' => $status === RentalStatus::Completed ? now() : null,
            'cancelled_at' => $status === RentalStatus::Cancelled ? now() : null,
            'cancellation_reason' => $status === RentalStatus::Cancelled ? 'Original cancellation' : null,
        ]);
        if ($status !== RentalStatus::PaymentPending) {
            Payment::create(['rental_id' => $rental->id, 'transaction_reference' => Payment::generateReference(), 'amount' => 830, 'paid_at' => now()]);
            SecurityDeposit::create([
                'rental_id' => $rental->id, 'amount' => 500,
                'status' => match ($status) {
                    RentalStatus::Completed => SecurityDepositStatus::ReturnEligible,
                    RentalStatus::Cancelled => SecurityDepositStatus::Refunded,
                    default => SecurityDepositStatus::Held,
                },
            ]);
        }
        if ($returned) {
            ConditionRecord::create(['rental_id' => $rental->id, 'recorded_by' => $owner->id, 'type' => 'after', 'condition' => 'good', 'notes' => 'Recorded after-condition evidence']);
        }

        return $rental->fresh();
    }

    public static function rentalStatuses(): array
    {
        return array_map(fn ($status) => [$status], RentalStatus::cases());
    }

    #[DataProvider('rentalStatuses')]
    public function test_admin_can_open_every_transaction_status_and_add_internal_notes(RentalStatus $status): void
    {
        $rental = $this->rental($status);
        $admin = User::factory()->admin()->create();
        $saved = $rental->getAttributes();
        $this->actingAs($admin)->get(route('admin.rentals.show', $rental))->assertOk()
            ->assertSee('Transaction #'.$rental->id)->assertSee('Transaction Owner')->assertSee('Transaction Renter');
        Livewire::test(AdminIndex::class)->assertSeeHtml(route('admin.rentals.show', $rental));
        Livewire::test(AdminShow::class, ['rental' => $rental])->set('note', '  Internal support note  ')
            ->call('addNote')->assertHasNoErrors()->assertSee('Internal support note')->assertSet('note', '');

        $entry = RentalAdminAction::sole();
        $this->assertSame(RentalAdminActionType::NoteAdded, $entry->action);
        $this->assertSame($admin->id, $entry->performed_by);
        $this->assertSame('Internal support note', $entry->notes);
        $this->assertSame($entry->before_state, $entry->after_state);
        $this->assertSame($saved, $rental->fresh()->getAttributes());
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_member_guests_and_inactive_admins_cannot_access_management(): void
    {
        $rental = $this->rental();
        $this->get(route('admin.rentals.show', $rental))->assertRedirect(route('login'));
        foreach ([$rental->owner, $rental->renter, User::factory()->create(), User::factory()->admin()->unverified()->create(), User::factory()->admin()->create(['status' => 'suspended'])] as $user) {
            Livewire::actingAs($user)->test(AdminIndex::class)->assertForbidden();
            Livewire::test(AdminShow::class, ['rental' => $rental])->assertForbidden();
            $this->assertDenied(fn () => RentalAdministration::perform($rental, $user, RentalAdminActionType::NoteAdded, 'Unauthorized'));
        }
        $this->assertDatabaseCount('rental_admin_actions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_admin_detail_displays_the_agreement_exchange_evidence_and_review(): void
    {
        $rental = $this->rental(RentalStatus::Completed);
        $rental->exchangeSchedule()->create([
            'proposed_by' => $rental->owner_id, 'fulfillment_method' => 'pickup',
            'scheduled_at' => now(), 'address' => 'Agreed Manila exchange address',
            'notes' => 'Meet at the reception', 'owner_confirmed_at' => now(),
            'renter_confirmed_at' => now(), 'confirmed_at' => now(),
        ]);
        $rental->afterConditionRecord->photos()->create(['path' => 'conditions/evidence.jpg', 'sort_order' => 0]);
        $claim = $rental->damageReport()->create([
            'condition_record_id' => $rental->afterConditionRecord->id, 'damage_type' => 'Scratch',
            'description' => 'Documented damage description', 'estimated_repair_cost' => 100,
            'proposed_deduction' => 100, 'status' => DamageReportStatus::Rejected,
        ]);
        $claim->photos()->create(['path' => 'damage/evidence.jpg', 'sort_order' => 0]);
        $rental->reviews()->create(['type' => 'renter_to_owner', 'rating' => 5, 'comment' => 'Completed transaction review']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.rentals.show', $rental))
            ->assertOk()->assertSee('Cancellation policy')->assertSee('Agreed Manila exchange address')
            ->assertSee('Confirmed by both parties')->assertSee('Recorded after-condition evidence')
            ->assertSeeHtml($rental->afterConditionRecord->photos->first()->url())
            ->assertSee('Documented damage description')->assertSeeHtml($claim->photos->first()->url())
            ->assertSee('Completed transaction review')->assertSee('Record deposit release');
    }

    public static function managementActions(): array
    {
        return [
            ['cancelBooking', RentalAdminActionType::BookingCancelled, RentalStatus::Paid],
            ['completeInspection', RentalAdminActionType::InspectionCompleted, RentalStatus::Returned],
            ['releaseDeposit', RentalAdminActionType::DepositReleased, RentalStatus::Completed],
            ['addNote', RentalAdminActionType::NoteAdded, RentalStatus::Active],
        ];
    }

    #[DataProvider('managementActions')]
    public function test_every_management_action_checks_admin_authority_and_required_notes(string $method, RentalAdminActionType $type, RentalStatus $status): void
    {
        $rental = $this->rental($status);
        foreach ([$rental->owner, $rental->renter, User::factory()->create()] as $member) {
            $this->assertDenied(fn () => RentalAdministration::perform($rental, $member, $type, 'Unauthorized change'));
        }
        $admin = User::factory()->admin()->create();
        $field = $type === RentalAdminActionType::NoteAdded ? 'note' : 'reason';
        $form = Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental]);
        foreach (['   ', str_repeat('x', 1001)] as $invalid) {
            $form->set($field, $invalid)->call($method)->assertHasErrors([$field]);
        }
        $this->assertDatabaseCount('rental_admin_actions', 0);
        $this->assertDatabaseCount('notifications', 0);
        $form->set($field, 'Permission revoked');
        $admin->update(['role' => 'member']);
        $form->call($method)->assertForbidden();
        $this->assertDatabaseCount('rental_admin_actions', 0);
    }

    public function test_admin_cancellation_uses_saved_terms_preserves_payment_and_frees_dates(): void
    {
        $rental = $this->rental(RentalStatus::Paid, 1);
        $payment = $rental->payment->getAttributes();
        $rental->listing->update(['price_per_day' => 9999]);
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Owner cannot fulfill this booking')->call('cancelBooking')->assertHasNoErrors();

        $rental->refresh();
        $this->assertSame(RentalStatus::Cancelled, $rental->status);
        $this->assertSame('60.00', $rental->cancellation_fee);
        $this->assertSame('cancelled', $rental->rentalRequest->status->value);
        $this->assertSame(SecurityDepositStatus::Refunded, $rental->securityDeposit->status);
        $this->assertSame($payment, $rental->payment->getAttributes());
        $this->assertFalse($rental->listing->hasApprovedOverlap($rental->start_date, $rental->end_date));
        $entry = RentalAdminAction::sole();
        $this->assertSame('paid', $entry->before_state['status']);
        $this->assertSame('cancelled', $entry->after_state['status']);
        $this->assertSame('refunded', $entry->after_state['deposit_status']);
        $this->assertNotificationPair($rental);
        Livewire::test(AdminShow::class, ['rental' => $rental])->set('reason', 'Repeated cancellation')->call('cancelBooking')->assertForbidden();
        $this->assertDatabaseCount('rental_admin_actions', 1);
    }

    public function test_admin_can_cancel_an_unpaid_booking_without_fabricating_payment_or_deposit(): void
    {
        $rental = $this->rental(RentalStatus::PaymentPending);
        Livewire::actingAs(User::factory()->admin()->create())->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Duplicate booking')->call('cancelBooking')->assertHasNoErrors();
        $this->assertSame(RentalStatus::Cancelled, $rental->fresh()->status);
        $this->assertSame('0.00', $rental->fresh()->cancellation_fee);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('security_deposits', 0);
        $this->assertNotificationPair($rental);
    }

    public function test_admin_cannot_cancel_after_either_handover_confirmation_or_after_rental_started(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (['pickup_confirmed_by_owner_at', 'pickup_confirmed_by_renter_at'] as $field) {
            $rental = $this->rental();
            $rental->update([$field => now()]);
            $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::BookingCancelled, 'Invalid cancellation'));
        }
        foreach ([RentalStatus::Active, RentalStatus::Overdue, RentalStatus::Returned, RentalStatus::Completed, RentalStatus::Cancelled] as $status) {
            $rental = $this->rental($status);
            $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::BookingCancelled, 'Invalid cancellation'));
        }
        $this->assertDatabaseCount('rental_admin_actions', 0);
    }

    public function test_admin_can_complete_inspection_and_archive_without_overwriting_party_confirmations(): void
    {
        $rental = $this->rental(RentalStatus::Returned);
        $ownerReturn = $rental->return_confirmed_by_owner_at;
        $renterReturn = $rental->return_confirmed_by_renter_at;
        Livewire::actingAs(User::factory()->admin()->create())->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Reviewed both return confirmations and condition evidence')->call('completeInspection')->assertHasNoErrors();
        $rental->refresh();
        $this->assertSame(RentalStatus::Completed, $rental->status);
        $this->assertTrue($rental->completed_at->equalTo($rental->archived_at));
        $this->assertTrue($rental->return_confirmed_by_owner_at->equalTo($ownerReturn));
        $this->assertTrue($rental->return_confirmed_by_renter_at->equalTo($renterReturn));
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->securityDeposit->status);
        $this->assertNotificationPair($rental);
        Livewire::test(AdminShow::class, ['rental' => $rental])->set('reason', 'Repeat')->call('completeInspection')->assertForbidden();
        $this->assertDatabaseCount('rental_admin_actions', 1);
    }

    public function test_admin_completion_requires_saved_owner_return_and_after_condition_evidence(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (['return_confirmed_by_owner_at', 'after_record', 'status'] as $missing) {
            $rental = $this->rental(RentalStatus::Returned);
            if ($missing === 'after_record') {
                $rental->afterConditionRecord()->delete();
            } else {
                $rental->update([$missing => $missing === 'status' ? RentalStatus::Active : null]);
            }
            $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::InspectionCompleted, 'Cannot bypass evidence'));
            $this->assertNull($rental->fresh()->archived_at);
        }
        $this->assertDatabaseCount('rental_admin_actions', 0);
    }

    public function test_admin_can_archive_an_inspected_owner_confirmed_return_without_renter_acknowledgement(): void
    {
        $rental = $this->rental(RentalStatus::Returned);
        $rental->update(['return_confirmed_by_renter_at' => null]);

        RentalAdministration::perform($rental, User::factory()->admin()->create(), RentalAdminActionType::InspectionCompleted, 'Owner return and condition evidence reviewed');

        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
        $this->assertNotNull($rental->fresh()->archived_at);
        $this->assertNull($rental->fresh()->return_confirmed_by_renter_at);
        $this->assertDatabaseCount('rental_admin_actions', 1);
    }

    public function test_admin_completion_preserves_the_damage_claim_and_holds_the_deposit(): void
    {
        $rental = $this->rental(RentalStatus::Returned);
        $rental->damageReport()->create(['condition_record_id' => $rental->afterConditionRecord->id, 'damage_type' => 'Scratch', 'description' => 'Damage evidence', 'estimated_repair_cost' => 100, 'proposed_deduction' => 100]);
        $admin = User::factory()->admin()->create();
        RentalAdministration::perform($rental, $admin, RentalAdminActionType::InspectionCompleted, 'Inspection reviewed; claim awaiting renter response');
        $this->assertSame(SecurityDepositStatus::DamageClaim, $rental->fresh()->securityDeposit->status);
        $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::DepositReleased, 'Invalid release'));
        $this->assertDatabaseCount('damage_reports', 1);
        $this->assertDatabaseCount('rental_admin_actions', 1);
    }

    public function test_admin_can_release_an_eligible_deposit_once_with_a_complete_audit_record(): void
    {
        $rental = $this->rental(RentalStatus::Completed);
        $saved = $rental->getAttributes();
        Livewire::actingAs(User::factory()->admin()->create())->test(AdminShow::class, ['rental' => $rental])
            ->set('reason', 'Agreed deposit refund arranged')->call('releaseDeposit')->assertHasNoErrors();
        $this->assertSame($saved, $rental->fresh()->getAttributes());
        $this->assertSame(SecurityDepositStatus::Released, $rental->fresh()->securityDeposit->status);
        $entry = RentalAdminAction::sole();
        $this->assertSame('return_eligible', $entry->before_state['deposit_status']);
        $this->assertSame('released', $entry->after_state['deposit_status']);
        $this->assertNotificationPair($rental);
        Livewire::test(AdminShow::class, ['rental' => $rental])->set('reason', 'Repeat release')->call('releaseDeposit')->assertForbidden();
        $this->assertDatabaseCount('rental_admin_actions', 1);
    }

    public function test_dispute_management_is_scoped_to_the_transaction_and_blocks_release_until_resolved(): void
    {
        $rental = $this->rental(RentalStatus::Completed);
        $other = $this->rental(RentalStatus::Completed);
        $dispute = $rental->disputes()->create(['raised_by' => $rental->renter_id, 'reason' => 'deposit_disagreement', 'description' => 'Review this deposit']);
        $other->disputes()->create(['raised_by' => $other->renter_id, 'reason' => 'deposit_disagreement', 'description' => 'Unrelated dispute']);
        $admin = User::factory()->admin()->create();
        $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::DepositReleased, 'Blocked by dispute'));
        $this->actingAs($admin)->get(route('admin.disputes.index', ['rentalId' => $rental->id]))
            ->assertOk()->assertSee('Review this deposit')->assertDontSee('Unrelated dispute');
        Livewire::test(AdminDisputes::class)->set('rentalId', $rental->id)->call('startResolving', $dispute->id)
            ->set('resolution', DisputeResolution::FullRefund->value)->set('resolution_notes', 'Deposit eligibility confirmed')->call('confirmResolve')->assertHasNoErrors();
        Livewire::test(AdminShow::class, ['rental' => $rental])->set('reason', 'Dispute resolved; release arranged')->call('releaseDeposit')->assertHasNoErrors();
        $this->assertSame(SecurityDepositStatus::Released, $rental->fresh()->securityDeposit->status);
        $this->assertNotificationPair($rental);
    }

    public function test_ineligible_missing_deposits_and_outstanding_damage_claims_cannot_be_released(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (SecurityDepositStatus::cases() as $status) {
            if ($status === SecurityDepositStatus::ReturnEligible) {
                continue;
            }
            $rental = $this->rental(RentalStatus::Completed);
            $rental->securityDeposit->update(['status' => $status]);
            $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::DepositReleased, 'Invalid release'));
        }
        $missing = $this->rental(RentalStatus::Completed);
        $missing->securityDeposit()->delete();
        $this->assertDenied(fn () => RentalAdministration::perform($missing, $admin, RentalAdminActionType::DepositReleased, 'Missing deposit'));
        foreach ([DamageReportStatus::Pending, DamageReportStatus::Disputed, DamageReportStatus::Accepted] as $status) {
            $rental = $this->rental(RentalStatus::Completed);
            $rental->damageReport()->create(['condition_record_id' => $rental->afterConditionRecord->id, 'damage_type' => 'Scratch', 'description' => 'Pending claim', 'estimated_repair_cost' => 100, 'proposed_deduction' => 100, 'status' => $status]);
            $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::DepositReleased, 'Invalid release'));
        }
        $this->assertDatabaseCount('rental_admin_actions', 0);
    }

    public function test_saved_state_and_locked_transaction_identity_prevent_stale_or_redirected_actions(): void
    {
        $rental = $this->rental();
        $admin = User::factory()->admin()->create();
        Rental::whereKey($rental->id)->update(['status' => RentalStatus::Active]);
        $this->assertDenied(fn () => RentalAdministration::perform($rental, $admin, RentalAdminActionType::BookingCancelled, 'Stale paid form'));
        $this->assertDatabaseCount('rental_admin_actions', 0);
        $other = $this->rental();
        $form = Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental]);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $form->set('rentalId', $other->id);
    }

    public function test_audit_failure_rolls_back_the_transaction_request_deposit_and_notifications(): void
    {
        $rental = $this->rental();
        $admin = User::factory()->admin()->create();
        RentalAdminAction::creating(fn () => throw new RuntimeException('Audit storage failure'));
        try {
            RentalAdministration::perform($rental, $admin, RentalAdminActionType::BookingCancelled, 'Must be atomic');
            $this->fail('Audit failure must prevent the transaction update.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit storage failure', $exception->getMessage());
        } finally {
            RentalAdminAction::flushEventListeners();
        }
        $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        $this->assertSame('approved', $rental->fresh()->rentalRequest->status->value);
        $this->assertSame(SecurityDepositStatus::Held, $rental->fresh()->securityDeposit->status);
        $this->assertDatabaseCount('rental_admin_actions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_admin_detail_and_audit_history_survive_listing_participant_and_admin_deletion(): void
    {
        $rental = $this->rental(RentalStatus::Completed);
        $admin = User::factory()->admin()->create();
        $entry = RentalAdministration::perform($rental, $admin, RentalAdminActionType::NoteAdded, 'Retained investigation note');
        $rental->owner->delete();
        $rental->renter->delete();
        $admin->delete();
        $viewer = User::factory()->admin()->create();
        $this->actingAs($viewer)->get(route('admin.rentals.show', $rental))->assertOk()
            ->assertSee('Management item')->assertSee('Listing removed')->assertSee('Deleted account')
            ->assertSee('Retained investigation note')->assertDontSee('@example.invalid');
        $this->assertSame($admin->id, $entry->fresh()->administrator->id);
        $this->assertTrue($entry->fresh()->administrator->trashed());
        Livewire::test(AdminIndex::class)->set('search', 'Management item')->assertSee('Management item');
    }

    public function test_admin_notes_are_private_and_the_audit_history_is_paginated(): void
    {
        $rental = $this->rental(RentalStatus::Active);
        $admin = User::factory()->admin()->create();
        for ($index = 1; $index <= 12; $index++) {
            RentalAdministration::perform($rental, $admin, RentalAdminActionType::NoteAdded, 'Private admin note '.$index);
        }
        $form = Livewire::actingAs($admin)->test(AdminShow::class, ['rental' => $rental]);
        $this->assertSame(12, $form->viewData('actions')->total());
        $this->assertCount(10, $form->viewData('actions')->items());
        $form->call('gotoPage', 2);
        $this->assertSame([2, 1], $form->viewData('actions')->getCollection()->pluck('id')->all());
        foreach (['owner' => $rental->owner, 'renter' => $rental->renter] as $interface => $party) {
            $this->actingAs($party)->get(route($interface.'.rentals.show', $rental))->assertOk()->assertDontSee('Private admin note');
        }
        $this->assertDatabaseCount('notifications', 0);
    }

    private function assertNotificationPair(Rental $rental): void
    {
        foreach (['owner' => $rental->owner, 'renter' => $rental->renter] as $interface => $party) {
            $notices = $party->notifications()->where('data->type', 'admin_transaction_update')->get();
            $this->assertCount(1, $notices);
            $this->assertSame(route($interface.'.rentals.show', $rental), $notices->first()->data['url']);
        }
    }

    private function assertDenied(callable $action): void
    {
        try {
            $action();
            $this->fail('This management action must be forbidden.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(403, $exception->status() ?? 403);
        }
    }
}
