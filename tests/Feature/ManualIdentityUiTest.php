<?php

namespace Tests\Feature;

use App\Livewire\Admin\IdentitySubmissions\Index as AdminReviews;
use App\Livewire\Identity\Verify;
use App\Models\IdentitySubmission;
use App\Models\User;
use App\Services\FaceComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManualIdentityUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->mock(FaceComparison::class)->shouldReceive('compare')->andReturn(0.63);
    }

    private function submit(User $member): IdentitySubmission
    {
        Livewire::actingAs($member)->test(Verify::class)->set('consent', true)
            ->set('front', UploadedFile::fake()->image('front.jpg', 640, 480))
            ->set('selfie', UploadedFile::fake()->image('selfie.jpg', 640, 480))
            ->call('submit')->assertHasNoErrors()->assertDispatched('identity-submitted')
            ->assertSet('front', null)->assertSet('selfie', null)->assertSet('consent', false)
            ->assertSee('Awaiting administrator review')->assertSee('Browse items');

        return IdentitySubmission::where('user_id', $member->id)->latest('id')->firstOrFail();
    }

    public function test_member_can_submit_and_cannot_choose_approval_or_a_face_score(): void
    {
        $member = User::factory()->create();
        $submission = $this->submit($member);
        $this->assertSame('pending', $submission->status);
        $this->assertSame(0.63, $submission->face_similarity);
        $this->assertFalse($member->fresh()->hasVerifiedIdentity());
        $this->actingAs($member)->get(route('identity.documents', [$submission, 'front']))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs(User::factory()->create())->get(route('identity.documents', [$submission, 'front']))->assertForbidden();
    }

    public function test_admin_queue_requires_all_checks_then_member_sees_reviewed_badge(): void
    {
        $member = User::factory()->create();
        $submission = $this->submit($member);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.identity-submissions.index'))->assertOk()->assertSee($member->name);
        Livewire::actingAs($admin)->test(AdminReviews::class)->call('selectSubmission', $submission->id)
            ->assertSee('0.6300')->assertSee('https://everify.gov.ph/check', false)
            ->call('review', 'approved')->assertHasErrors(['qr_checked', 'details_match', 'face_reviewed'])
            ->set('qrChecked', true)->set('detailsMatch', true)->set('faceReviewed', true)
            ->call('review', 'approved')->assertHasNoErrors()->assertSee('Review photos are no longer available');
        $this->assertTrue($member->fresh()->hasVerifiedIdentity());
        $this->actingAs($member)->get(route('identity.verify'))->assertOk()->assertSee('ID reviewed')
            ->assertDontSee('Submit for review');
        $this->get(route('users.show', $member))->assertSee('ID reviewed')->assertDontSee('manual-review:');
    }

    public function test_rejected_member_sees_reason_and_can_submit_again(): void
    {
        $member = User::factory()->create();
        $submission = $this->submit($member);
        Livewire::actingAs(User::factory()->admin()->create())->test(AdminReviews::class)
            ->call('selectSubmission', $submission->id)->set('notes', 'Please upload a readable QR code.')
            ->call('review', 'rejected')->assertHasNoErrors();
        $this->actingAs($member)->get(route('identity.verify'))->assertSee('Please upload a readable QR code.');
        $next = $this->submit($member);
        $this->assertNotSame($next->id, $submission->id);
        $this->assertSame('rejected', $submission->fresh()->status);
    }

    public function test_members_and_demoted_administrators_cannot_review(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.identity-submissions.index'))->assertForbidden();
        Livewire::test(AdminReviews::class)->assertForbidden();
        $member = User::factory()->create();
        $submission = $this->submit($member);
        $admin = User::factory()->admin()->create();
        $component = Livewire::actingAs($admin)->test(AdminReviews::class)->call('selectSubmission', $submission->id);
        User::whereKey($admin->id)->update(['role' => 'member']);
        $component->set('qrChecked', true)->assertForbidden();
        $this->assertSame('pending', $submission->fresh()->status);
    }

    public function test_expiry_prunes_only_old_pending_documents_and_allows_resubmission(): void
    {
        $member = User::factory()->create();
        $old = $this->submit($member);
        $old->update(['created_at' => now()->subDays(31)]);
        $recent = $this->submit(User::factory()->create());
        $this->artisan('identity:prune-documents')->assertSuccessful();
        $this->assertSame('cancelled', $old->fresh()->status);
        $this->assertSame('pending', $recent->fresh()->status);
        $this->actingAs($member)->get(route('identity.documents', [$old, 'front']))->assertNotFound();
        $this->submit($member);
        $this->assertDatabaseCount('identity_submissions', 3);
    }
}
