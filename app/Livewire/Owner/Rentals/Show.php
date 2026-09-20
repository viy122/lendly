<?php

namespace App\Livewire\Owner\Rentals;

use App\Enums\ConditionRecordType;
use App\Enums\DisputeReason;
use App\Enums\ListingCondition;
use App\Enums\NotificationType;
use App\Enums\ReviewType;
use App\Models\ConditionRecord;
use App\Models\ConditionRecordPhoto;
use App\Models\DamageReport;
use App\Models\DamageReportPhoto;
use App\Models\Dispute;
use App\Models\Rental;
use App\Models\Review;
use App\Notifications\TalaNotification;
use App\Services\RentalLifecycle;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithFileUploads;

    public Rental $rental;

    public string $before_condition = 'good';

    public string $before_notes = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $before_photos = [];

    public string $after_condition = 'good';

    public string $after_notes = '';

    public bool $after_has_damage = false;

    /** @var array<int, TemporaryUploadedFile> */
    public array $after_photos = [];

    public string $damage_type = '';

    public string $damage_description = '';

    public ?float $damage_estimated_cost = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $damage_photos = [];

    public int $renter_rating = 5;

    public string $renter_comment = '';

    public string $dispute_reason = '';

    public string $dispute_description = '';

    public bool $showDisputeForm = false;

    public function mount(Rental $rental): void
    {
        $this->authorize('view', $rental);

        $this->rental = $rental->load([
            'listing', 'owner', 'renter', 'payment', 'securityDeposit',
            'beforeConditionRecord.photos', 'afterConditionRecord.photos', 'damageReport.photos',
            'reviewFromOwnerToRenter', 'disputes',
        ]);
    }

    public function confirmPickup(): void
    {
        $this->authorize('confirmPickup', $this->rental);

        RentalLifecycle::confirmPickup($this->rental, auth()->user());

        $this->rental->refresh();
    }

    public function confirmReturn(): void
    {
        $this->authorize('confirmReturn', $this->rental);

        RentalLifecycle::confirmReturn($this->rental, auth()->user());

        $this->rental->refresh();
    }

    public function recordBeforeCondition(): void
    {
        $this->authorize('recordBeforeCondition', $this->rental);

        $this->validate([
            'before_condition' => ['required', Rule::enum(ListingCondition::class)],
            'before_notes' => ['nullable', 'string', 'max:1000'],
            'before_photos.*' => ['nullable', 'image', 'max:4096'],
        ]);

        $record = ConditionRecord::create([
            'rental_id' => $this->rental->id,
            'recorded_by' => auth()->id(),
            'type' => ConditionRecordType::Before,
            'condition' => $this->before_condition,
            'notes' => $this->before_notes,
        ]);

        $this->storePhotos($this->before_photos, ConditionRecordPhoto::class, 'condition_record_id', $record->id);

        $this->rental->refresh()->load('beforeConditionRecord.photos');
        $this->reset('before_notes', 'before_photos');
    }

    public function recordAfterCondition(): void
    {
        $this->authorize('recordAfterCondition', $this->rental);

        $this->validate([
            'after_condition' => ['required', Rule::enum(ListingCondition::class)],
            'after_notes' => ['nullable', 'string', 'max:1000'],
            'after_photos.*' => ['nullable', 'image', 'max:4096'],
            'damage_type' => [Rule::requiredIf($this->after_has_damage), 'nullable', 'string', 'max:255'],
            'damage_description' => [Rule::requiredIf($this->after_has_damage), 'nullable', 'string', 'max:1000'],
            'damage_estimated_cost' => [Rule::requiredIf($this->after_has_damage), 'nullable', 'numeric', 'min:0'],
        ]);

        $record = ConditionRecord::create([
            'rental_id' => $this->rental->id,
            'recorded_by' => auth()->id(),
            'type' => ConditionRecordType::After,
            'condition' => $this->after_condition,
            'notes' => $this->after_notes,
            'has_damage' => $this->after_has_damage,
        ]);

        $this->storePhotos($this->after_photos, ConditionRecordPhoto::class, 'condition_record_id', $record->id);

        if ($this->after_has_damage) {
            $depositAmount = (float) $this->rental->security_deposit;
            $proposedDeduction = min((float) $this->damage_estimated_cost, $depositAmount);

            $damageReport = DamageReport::create([
                'rental_id' => $this->rental->id,
                'condition_record_id' => $record->id,
                'damage_type' => $this->damage_type,
                'description' => $this->damage_description,
                'estimated_repair_cost' => $this->damage_estimated_cost,
                'proposed_deduction' => $proposedDeduction,
            ]);

            $this->storePhotos($this->damage_photos, DamageReportPhoto::class, 'damage_report_id', $damageReport->id);
        }

        $this->rental->refresh()->load('afterConditionRecord.photos', 'damageReport.photos');
        $this->reset('after_notes', 'after_has_damage', 'after_photos', 'damage_type', 'damage_description', 'damage_estimated_cost', 'damage_photos');
    }

    public function completeInspection(): void
    {
        $this->authorize('completeInspection', $this->rental);

        RentalLifecycle::completeInspection($this->rental);

        $this->rental->refresh()->load('securityDeposit');
    }

    public function releaseDeposit(): void
    {
        $this->authorize('releaseDeposit', $this->rental);

        RentalLifecycle::releaseDeposit($this->rental);

        $this->rental->refresh()->load('securityDeposit');
    }

    public function submitRenterReview(): void
    {
        abort_unless(auth()->id() === $this->rental->owner_id, 403);
        abort_unless($this->rental->isCompleted(), 403);
        abort_if($this->rental->reviewFromOwnerToRenter, 403, 'You already reviewed this renter.');

        $this->validate([
            'renter_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'renter_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::create([
            'rental_id' => $this->rental->id,
            'type' => ReviewType::OwnerToRenter,
            'rating' => $this->renter_rating,
            'comment' => $this->renter_comment,
        ]);

        $this->rental->refresh()->load('reviewFromOwnerToRenter');
    }

    public function createDispute(): void
    {
        $this->validate([
            'dispute_reason' => ['required', Rule::enum(DisputeReason::class)],
            'dispute_description' => ['required', 'string', 'max:1000'],
        ]);

        Dispute::create([
            'rental_id' => $this->rental->id,
            'raised_by' => auth()->id(),
            'reason' => $this->dispute_reason,
            'description' => $this->dispute_description,
        ]);

        $this->rental->renter->notify(new TalaNotification(
            NotificationType::DisputeUpdate->value,
            'New dispute raised',
            "The owner raised a dispute for \"{$this->rental->listing->name}\".",
            route('renter.rentals.show', $this->rental),
        ));

        session()->flash('status', 'Your dispute has been submitted. An admin will review it.');

        $this->reset('dispute_reason', 'dispute_description', 'showDisputeForm');
        $this->rental->refresh()->load('disputes');
    }

    /**
     * @param  class-string  $photoModel
     */
    private function storePhotos(array $photos, string $photoModel, string $foreignKey, int $ownerId): void
    {
        foreach ($photos as $index => $photo) {
            $path = $photo->store('condition-photos', 'public');

            $photoModel::create([
                $foreignKey => $ownerId,
                'path' => $path,
                'sort_order' => $index,
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.owner.rentals.show');
    }
}
