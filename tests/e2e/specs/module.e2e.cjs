// Module-level tests: each business module exercised on its own, starting from a prepared state
// (tests/e2e/fixtures/seed.php) instead of replaying the whole rental flow. Covers the unhappy
// paths too: decline, auto-decline, cancellation fees, rejected payments, damage and disputes.
const assert = require('assert');
const a = require('../support/actions.cjs');
const { fixtures, freshSession, loginAs, asAdmin } = require('../support/session.cjs');
const LoginPage = require('../pages/LoginPage.cjs');

let fx;
const reviewBox = () => $('textarea[id^="payment-review-"]');

describe('Module tests', () => {
  before(() => {
    fx = fixtures();
  });

  describe('Listings module (owner)', () => {
    it('M-01 owner edits a listing price and the new price is public', async () => {
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url(`/owner/listings/${fx.listings.ladder.id}/edit`);
      await a.type('#price_per_day', '275');
      await browser.pause(1200);
      await a.submitWireForm('save');
      await a.waitForText('Listing updated.');
      await browser.url(`/listings/${fx.listings.ladder.id}`);
      await a.waitForText('₱275.00');
    });

    it('M-02 owner pauses a listing and it disappears from the marketplace', async () => {
      await browser.url('/owner/listings');
      await a.clickWire(`deactivate(${fx.listings.ladder.id})`);
      await browser.pause(1500);
      await freshSession();
      await browser.url('/listings');
      await a.type('input[aria-label="Search items"]', fx.listings.ladder.name);
      await a.waitForText('No listings match your filters');
    });

    it('M-03 owner reactivates the listing and it is public again', async () => {
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url('/owner/listings');
      await a.clickWire(`reactivate(${fx.listings.ladder.id})`);
      await browser.pause(1500);
      await freshSession();
      await browser.url('/listings');
      await a.type('input[aria-label="Search items"]', fx.listings.ladder.name);
      await a.waitForText(fx.listings.ladder.name);
    });
  });

  describe('Rental requests module', () => {
    it('M-04 owner declines a request with a reason', async () => {
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url('/owner/rental-requests');
      await a.clickWire(`startRejecting(${fx.requests.reqDecline})`);
      await a.type('#rejection_reason', 'QA: item is being serviced that week.');
      await a.clickWire('confirmReject');
      await browser.pause(1500);
      await a.assertNoServerError();
    });

    it('M-05 renter sees the decline and the owner\'s reason', async () => {
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url('/renter/rental-requests');
      await a.waitForText('QA: item is being serviced that week.');
    });

    it('M-06 approving one request auto-declines an overlapping request', async () => {
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url('/owner/rental-requests');
      await a.clickWire(`startApproving(${fx.requests.reqOverlapA})`);
      await a.clickWire(`approve(${fx.requests.reqOverlapA})`);
      await a.waitForUrl(new RegExp(`/owner/rental-requests/${fx.requests.reqOverlapA}/agreement`));
      await loginAs(fx.users.renter2.email, 'renter');
      await browser.url('/renter/rental-requests');
      await a.waitForText('These dates were booked by another renter.');
    });

    it('M-07 renter cancels their own pending request', async () => {
      await a.clickWire(`startCancelling(${fx.requests.reqCancel})`);
      await a.type(`#cancellation-reason-${fx.requests.reqCancel}`, 'QA: plans changed.');
      await a.scrollClick(`form[wire\\:submit="cancel(${fx.requests.reqCancel})"] button[type="submit"]`);
      await browser.pause(1500);
      await a.assertNoServerError();
      await a.waitForText('Cancelled');
    });
  });

  describe('Agreement and booking module', () => {
    it('M-08 renter accepts the agreement first; no booking yet', async () => {
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url(`/renter/rental-requests/${fx.requests.reqAgree}/agreement`);
      await a.scrollClick('form[wire\\:submit="acceptTerms"] input[type="checkbox"]');
      await a.submitWireForm('acceptTerms');
      await browser.pause(1500);
      await browser.url('/renter/rentals');
      assert.ok(!(await a.pageText()).includes(fx.listings.speaker.name), 'booking created after only one acceptance');
    });

    it('M-09 owner accepts too → booking confirmed and both are notified', async () => {
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url(`/owner/rental-requests/${fx.requests.reqAgree}/agreement`);
      await a.scrollClick('form[wire\\:submit="acceptTerms"] input[type="checkbox"]');
      await a.submitWireForm('acceptTerms');
      await browser.pause(1500);
      await browser.url('/owner/rentals');
      await a.waitForText(fx.listings.speaker.name);
      await browser.url('/notifications');
      await a.waitForText('Booking confirmed');
    });
  });

  describe('Payments module', () => {
    it('M-10 renter submits payment proof for a confirmed booking', async () => {
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url(`/renter/rentals/${fx.rentals.pay}`);
      await a.type('#payment-reference', 'MOD-PAY-1');
      await a.upload('#payment-proof');
      await browser.pause(2000);
      await a.submitWireForm('submitPaymentProof');
      await a.waitForText('Payment proof submitted');
    });

    it('M-11 admin rejects the proof with a reason', async () => {
      await asAdmin();
      await browser.url(`/admin/rentals/${fx.rentals.pay}`);
      await a.scrollClick('#rental-tab-payments');
      await (await reviewBox()).waitForDisplayed();
      await (await reviewBox()).setValue('QA: receipt is blurry, please upload a clearer copy.');
      await a.clickWire(`rejectPayment(${await (await reviewBox()).getAttribute('id').then((id) => id.replace('payment-review-', ''))})`);
      await a.waitForText('rejected', 15000);
    });

    it('M-12 renter sees the rejection and resubmits new proof', async () => {
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url(`/renter/rentals/${fx.rentals.pay}`);
      await a.waitForText('QA: receipt is blurry');
      await a.type('#payment-reference', 'MOD-PAY-2');
      await a.upload('#payment-proof');
      await browser.pause(2000);
      await a.submitWireForm('submitPaymentProof');
      await a.waitForText('Payment proof submitted');
    });

    it('M-13 admin verifies the new proof → booking Paid', async () => {
      await asAdmin();
      await browser.url(`/admin/rentals/${fx.rentals.pay}`);
      await a.scrollClick('#rental-tab-payments');
      await (await reviewBox()).waitForDisplayed();
      await (await reviewBox()).setValue('QA: amount matches the bank statement.');
      const funds = await $('input[type="checkbox"][wire\\:model="funds_received"]');
      if (!(await funds.isSelected())) await funds.click();
      const sid = (await (await reviewBox()).getAttribute('id')).replace('payment-review-', '');
      await a.clickWire(`verifyPayment(${sid})`);
      await browser.pause(2000);
      await browser.refresh();
      await a.waitForText('Paid');
    });

    it('M-14 paid item shows as Reserved on its public page (FR-42)', async () => {
      await freshSession();
      await browser.url(`/listings/${fx.listings.sony.id}`);
      await a.waitForText('Reserved');
    });

    it('M-15 both parties see the transaction receipt (FR-17)', async () => {
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url(`/renter/rentals/${fx.rentals.pay}`);
      await a.waitForText('Transaction receipt');
      await a.waitForText('MOD-PAY-2');
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url(`/owner/rentals/${fx.rentals.pay}`);
      await a.waitForText('Transaction receipt');
    });
  });

  describe('Cancellation module', () => {
    it('M-16 cancelling within 48 hours charges the 20% fee (₱240 of ₱1,200)', async () => {
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url(`/renter/rentals/${fx.rentals.cancelFee}`);
      await a.clickWire("$toggle('showCancelForm')");
      await a.type('#cancellation_reason', 'QA: no longer needed.');
      await a.clickWire('cancelRental');
      await a.waitForText('This booking was cancelled.');
      await a.waitForText('Cancellation fee: ₱240.00');
    });

    it('M-17 cancelling 10 days ahead is free (₱0 fee)', async () => {
      await browser.url(`/renter/rentals/${fx.rentals.cancelFree}`);
      await a.clickWire("$toggle('showCancelForm')");
      await a.clickWire('cancelRental');
      await a.waitForText('This booking was cancelled.');
      await a.waitForText('Cancellation fee: ₱0.00');
    });
  });

  describe('Return, damage and dispute module', () => {
    it('M-18 renter confirms the return of an active rental', async () => {
      await browser.url(`/renter/rentals/${fx.rentals.return}`);
      await a.clickWire('confirmReturn');
      await browser.pause(1500);
      assert.ok(!(await $('[wire\\:click="confirmReturn"]').isExisting()), 'return button still shown');
    });

    it('M-19 owner records damage (₱300) and closes the return', async () => {
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url(`/owner/rentals/${fx.rentals.return}`);
      await a.type('#after_notes', 'QA: crack on the body.');
      await a.scrollClick('input[type="checkbox"][wire\\:model\\.live="after_has_damage"]');
      await a.type('#damage_type', 'Cracked body');
      await a.type('#damage_description', 'QA: 3 cm crack near the sound hole.');
      await a.type('#damage_estimated_cost', '300');
      await a.submitWireForm('recordAfterCondition');
      await a.waitForText('QA: crack on the body.');
      await a.clickWire('confirmReturn');
      await a.waitForText('Completed');
    });

    it('M-20 renter disputes the damage claim', async () => {
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url(`/renter/rentals/${fx.rentals.return}`);
      await a.type('#damage_response_notes', 'QA: the crack was there before pickup.');
      await a.clickWire('disputeDamageClaim');
      await browser.pause(1500);
      await a.assertNoServerError();
      await a.waitForText('You disputed this claim.');
    });

    it('M-21 admin resolves the dispute with ₱150 partial compensation', async () => {
      await asAdmin();
      await browser.url('/admin/disputes');
      await a.scrollClick('[wire\\:click^="startResolving("]');
      await a.setField('#resolution', 'partial_compensation');
      await browser.pause(1000);
      await a.type('#partial_amount', '150');
      await a.type('#resolution_notes', 'QA: shared responsibility.');
      await a.submitWireForm('confirmResolve');
      await browser.pause(1500);
      await a.assertNoServerError();
      await loginAs(fx.users.renter.email, 'renter');
      await browser.url('/notifications');
      await a.waitForText('Dispute resolved');
    });
  });

  describe('Messaging module', () => {
    it('M-22 renter messages the owner from a listing', async () => {
      await browser.url(`/listings/${fx.listings.tripod.id}/message`);
      await a.type('form[wire\\:submit="send"] [wire\\:model="body"]', 'QA: is the tripod still available this weekend?');
      await a.submitWireForm('send');
      await a.waitForText('QA: is the tripod still available this weekend?');
    });

    it('M-23 owner sees the message in the inbox and replies', async () => {
      await loginAs(fx.users.owner.email, 'owner');
      await browser.url('/messages');
      await a.waitForText(fx.users.renter.name);
      const href = await browser.execute(() => {
        const link = Array.from(document.querySelectorAll('a[href*="conversation"], a[href*="/messages/"], a[href*="listing-conversations"]'))[0];
        return link && link.getAttribute('href');
      });
      assert.ok(href, 'no conversation link in the inbox');
      await browser.url(href);
      await a.type('form[wire\\:submit="send"] [wire\\:model="body"]', 'QA: yes, it is available.');
      await a.submitWireForm('send');
      await a.waitForText('QA: yes, it is available.');
    });
  });

  describe('Admin moderation module', () => {
    it('M-24 admin removes a live listing; it leaves the marketplace', async () => {
      await asAdmin();
      // The list shows 10 per page; find the page that holds the listing.
      for (let page = 1; page <= 5; page++) {
        await browser.url(`/admin/listings?page=${page}`);
        if (await $(`[wire\\:click="remove(${fx.listings.suspicious.id})"]`).isExisting()) break;
      }
      await a.clickWire(`remove(${fx.listings.suspicious.id})`);
      await browser.pause(1500);
      await a.assertNoServerError();
      await freshSession();
      await browser.url('/listings');
      await a.type('input[aria-label="Search items"]', fx.listings.suspicious.name);
      await a.waitForText('No listings match your filters');
    });

    it('M-25 admin suspends an account and that user can no longer log in', async () => {
      await asAdmin();
      await browser.url('/admin/dashboard');
      await a.clickWire(`toggleSuspension(${fx.users.suspend.id})`);
      await a.waitForText('Suspended');
      await LoginPage.open();
      await LoginPage.loginAs(fx.users.suspend.email, 'password');
      await a.waitForText('Your account has been suspended.');
    });
  });
});
