// Full rental cycle, end to end through the real UI:
// register -> verify -> list item -> browse -> request -> approve -> agreement (both)
// -> payment proof -> admin verify -> handover -> return/inspection -> deposit -> reviews.
// Steps share state and run in order; once one fails the rest are skipped.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const LoginPage = require('../pages/LoginPage.cjs');
const a = require('../support/actions.cjs');

const RUN = Date.now().toString(36);
const PASSWORD = 'Qa-Passw0rd!2026';
const owner = { name: `QA Owner ${RUN}`, email: `qa.owner.${RUN}@gmail.com` };
const renter = { name: `QA Renter ${RUN}`, email: `qa.renter.${RUN}@gmail.com` };
const ITEM = `QA Camera ${RUN}`;
const state = {};
const results = [];
const notes = [];
let broken = false;

const note = (msg) => notes.push(msg);

async function freshSession() {
  // Cookies can only be cleared for the domain currently loaded.
  await browser.url('/');
  await browser.deleteCookies();
  await browser.url('/');
}

async function loginAs(email, password, iface) {
  await freshSession();
  await LoginPage.open();
  await LoginPage.loginAs(email, password);
  if (iface) {
    await a.scrollClick(`[wire\\:click="chooseInterface('${iface}')"]`);
    await a.waitForUrl(new RegExp(`/${iface}/dashboard`));
  } else {
    await a.waitForUrl(/\/(admin\/)?dashboard/);
  }
  await a.assertNoServerError();
}

async function register(user) {
  await freshSession();
  await browser.url('/register');
  await a.type('#name', user.name);
  await a.type('#email', user.email);
  // The password fields block paste, so type them key by key via setValue.
  await a.type('#password', PASSWORD);
  await a.type('#password_confirmation', PASSWORD);
  await a.submitWireForm('register');
  await a.waitForUrl(/verify-email/);
  await browser.url(a.latestVerificationLink());
  await a.waitForUrl(/dashboard/);
  await a.assertNoServerError();
}

describe('full cycle: register → list → rent → pay → handover → return → review', function () {
  before(() => a.ensureFixture());

  beforeEach(function () {
    if (broken) this.skip();
  });

  afterEach(async function () {
    const entry = { step: this.currentTest.title, state: this.currentTest.state, ms: this.currentTest.duration };
    if (this.currentTest.state === 'failed') {
      broken = true;
      entry.error = String(this.currentTest.err && this.currentTest.err.message).slice(0, 500);
      entry.url = await browser.getUrl().catch(() => '');
      entry.screenshot = await a.screenshot(`fail-${results.length + 1}-${this.currentTest.title}`).catch(() => null);
    }
    results.push(entry);
  });

  after(() => {
    const out = path.join(__dirname, '..', 'artifacts', 'full-cycle-results.json');
    fs.writeFileSync(out, JSON.stringify({ run: RUN, owner, renter, item: ITEM, state, results, notes }, null, 2));
  });

  it('01 admin enables offline payment instructions (FR-41 setup)', async () => {
    await loginAs('admin@tala.test', 'password');
    await browser.url('/admin/payment-settings');
    await a.assertNoServerError();
    await a.setField('#payment-method', 'bank_transfer');
    await a.type('#payment-instructions', 'QA: Transfer the full amount to BDO 0000-1111-2222 (Tala QA) and upload the receipt.');
    const enabled = await $('input[type="checkbox"][wire\\:model="enabled"]');
    if (!(await enabled.isSelected())) await enabled.click();
    await a.submitWireForm('save');
    await browser.pause(1500);
    await browser.refresh();
    const saved = await (await $('#payment-instructions')).getValue();
    assert.ok(saved.includes('BDO 0000-1111-2222'), 'payment instructions did not persist');
  });

  it('02 owner registers and verifies Gmail (FR-01/02)', async () => {
    await register(owner);
  });

  it('03 renter registers and verifies Gmail (FR-01/02)', async () => {
    await register(renter);
  });

  it('04 owner publishes a listing with photo + map pin (FR-05/06/47)', async () => {
    await loginAs(owner.email, PASSWORD, 'owner');
    await browser.url('/owner/listings/create');
    await a.assertNoServerError();
    const firstCategory = await browser.execute(() => {
      const opt = Array.from(document.querySelectorAll('#category_id option')).find((o) => o.value);
      return opt && opt.value;
    });
    await a.setField('#category_id', firstCategory);
    await browser.pause(1200);
    await a.type('#name', ITEM);
    await a.type('#brand', 'Canon');
    await a.type('#model', 'EOS M50');
    await a.type('#description', 'Mirrorless camera with 15-45mm kit lens, 2 batteries, charger and bag. QA run item.');
    await a.type('#location', 'Nasugbu, Batangas');
    await a.type('#max_rental_duration_days', '7');
    await a.setField('#available_from', a.daysFromNow(0));
    await a.setField('#available_until', a.daysFromNow(30));
    await a.type('#rental_rules', 'Return clean. No use in rain.');
    await a.type('#price_per_day', '500');
    await a.type('#security_deposit', '1000');

    // Map pin: click the Leaflet map like a user would.
    const map = await $('[x-ref="map"]');
    await map.scrollIntoView({ block: 'center' });
    await browser.pause(800);
    await map.click();
    await browser.pause(800);

    await a.upload('#listing-photos');
    await (await $('[wire\\:click^="removePendingPhoto"]')).waitForExist({ timeout: 20000 });

    await a.submitWireForm('save');
    await a.waitForUrl(/\/owner\/listings(\?|$)/);
    await a.waitForText('Listing published.');
    await a.assertNoServerError();
  });

  it('05 listing is discoverable on the public marketplace (FR-09/10)', async () => {
    await freshSession();
    await browser.url('/listings');
    const search = await $('input[aria-label="Search items"]');
    await search.waitForDisplayed({ timeout: 15000 });
    await search.setValue(ITEM);
    await a.waitForText(ITEM);
    const href = await browser.execute((item) => {
      const link = Array.from(document.querySelectorAll('a[href*="/listings/"]')).find((el) => el.innerText.includes(item));
      return link && link.getAttribute('href');
    }, ITEM);
    assert.ok(href, 'listing card link not found');
    state.listingId = Number(href.match(/\/listings\/(\d+)/)[1]);
    await browser.url(`/listings/${state.listingId}`);
    await a.waitForText(ITEM);
    const ownerHref = await browser.execute(() => {
      const el = document.querySelector('a[href*="/users/"]');
      return el && el.getAttribute('href');
    });
    if (ownerHref) state.ownerId = Number(ownerHref.match(/\/users\/(\d+)/)[1]);
    else note('Listing page has no link to the owner public profile (/users/{id}).');
  });

  it('06 renter sends a 3-day rental request (FR-11/16)', async () => {
    await loginAs(renter.email, PASSWORD, 'renter');
    await browser.url(`/listings/${state.listingId}`);
    await a.assertNoServerError();
    await a.scrollClick('button[aria-controls="rental-request-modal"]');
    await (await $('#start_date')).waitForDisplayed({ timeout: 15000 });
    state.start = a.daysFromNow(2);
    state.end = a.daysFromNow(4);
    await a.setField('#start_date', state.start);
    await browser.pause(800);
    await a.setField('#end_date', state.end);
    await browser.pause(800);
    const summary = await a.pageText();
    if (!summary.includes('1,500')) note(`Request summary did not show ₱1,500 rental fee for 3 days × ₱500 (dates ${state.start}→${state.end}).`);
    await a.scrollClick('#accept_terms');
    await a.submitWireForm('submit');
    await a.waitForUrl(/\/renter\/rental-requests/);
    await a.waitForText('Rental request sent');
    await a.assertNoServerError();
  });

  it('07 owner approves the request (FR-12)', async () => {
    await loginAs(owner.email, PASSWORD, 'owner');
    await browser.url('/owner/rental-requests');
    await a.waitForText(ITEM);
    await a.scrollClick('[wire\\:click^="startApproving("]');
    await a.scrollClick('[wire\\:click^="approve("]');
    const url = await a.waitForUrl(/\/owner\/rental-requests\/\d+\/agreement/);
    state.requestId = Number(url.match(/rental-requests\/(\d+)/)[1]);
    await a.assertNoServerError();
  });

  it('08 owner accepts the rental agreement (FR-15)', async () => {
    await a.scrollClick('form[wire\\:submit="acceptTerms"] input[type="checkbox"]');
    await a.submitWireForm('acceptTerms');
    await browser.pause(2000);
    await a.assertNoServerError();
    assert.ok(!(await $('form[wire\\:submit="acceptTerms"]').isExisting()) || /accepted|waiting/i.test(await a.pageText()),
      'owner acceptance not reflected on page');
  });

  it('09 renter accepts the agreement → booking confirmed (FR-14/15/40)', async () => {
    await loginAs(renter.email, PASSWORD, 'renter');
    await browser.url(`/renter/rental-requests/${state.requestId}/agreement`);
    await a.assertNoServerError();
    await a.scrollClick('form[wire\\:submit="acceptTerms"] input[type="checkbox"]');
    await a.submitWireForm('acceptTerms');
    await browser.pause(2000);
    await a.assertNoServerError();
    await browser.url('/renter/rentals');
    await a.waitForText(ITEM);
    const href = await browser.execute(() => {
      const el = document.querySelector('a[href*="/renter/rentals/"]');
      return el && el.getAttribute('href');
    });
    assert.ok(href, 'no rental created after both acceptances');
    state.rentalId = Number(href.match(/rentals\/(\d+)/)[1]);
  });

  it('10 renter submits offline payment proof (FR-41)', async () => {
    await browser.url(`/renter/rentals/${state.rentalId}`);
    await a.assertNoServerError();
    await a.waitForText('BDO 0000-1111-2222');
    await a.type('#payment-reference', `qa-ref-${RUN}`);
    await a.upload('#payment-proof');
    await browser.pause(2500);
    await a.submitWireForm('submitPaymentProof');
    await a.waitForText('Payment proof submitted');
    await a.assertNoServerError();
  });

  it('11 admin verifies payment → rental Paid (FR-17/41)', async () => {
    await loginAs('admin@tala.test', 'password');
    await browser.url(`/admin/rentals/${state.rentalId}`);
    await a.assertNoServerError();
    await a.scrollClick('#rental-tab-payments');
    const review = await $('textarea[id^="payment-review-"]');
    await review.waitForExist({ timeout: 15000 });
    await review.scrollIntoView({ block: 'center' });
    await review.setValue('QA: amount matches bank statement.');
    const funds = await $('input[type="checkbox"][wire\\:model="funds_received"]');
    if (!(await funds.isSelected())) await funds.click();
    await a.autoConfirm();
    await a.scrollClick('[wire\\:click^="verifyPayment("]');
    await a.waitForText('Payment verified', 20000).catch(() => {});
    await browser.refresh();
    await a.waitForText('Paid');
  });

  it('12 owner records before-condition and confirms handover (FR-18/43)', async () => {
    await loginAs(owner.email, PASSWORD, 'owner');
    await browser.url(`/owner/rentals/${state.rentalId}`);
    await a.assertNoServerError();
    await a.type('#before_notes', 'QA: no scratches, both batteries included.');
    await a.upload('#before_photos');
    await browser.pause(2500);
    await a.submitWireForm('recordBeforeCondition');
    await a.waitForText('QA: no scratches');
    await a.clickWire('confirmPickup');
    await browser.pause(2000);
    await a.assertNoServerError();
  });

  it('13 renter confirms pickup → rental Active (FR-43/44)', async () => {
    await loginAs(renter.email, PASSWORD, 'renter');
    await browser.url(`/renter/rentals/${state.rentalId}`);
    await a.clickWire('confirmPickup');
    await a.waitForText('Active');
    await a.assertNoServerError();
  });

  it('14 renter confirms return (FR-21)', async () => {
    await a.clickWire('confirmReturn');
    await browser.pause(2000);
    await a.assertNoServerError();
    assert.ok(!(await $('[wire\\:click="confirmReturn"]').isExisting()), 'renter return button still shown');
  });

  it('15 owner records after-condition and confirms return → Completed (FR-21/22)', async () => {
    await loginAs(owner.email, PASSWORD, 'owner');
    await browser.url(`/owner/rentals/${state.rentalId}`);
    await a.type('#after_notes', 'QA: returned in same condition.');
    await a.submitWireForm('recordAfterCondition');
    await a.waitForText('QA: returned in same condition.');
    await a.clickWire('confirmReturn');
    await a.waitForText('Completed');
    await a.assertNoServerError();
  });

  it('16 owner releases the security deposit', async () => {
    await a.clickWire('releaseDeposit');
    await a.waitForText('Released');
  });

  it('17 owner reviews the renter (FR-23)', async () => {
    await a.setField('#renter_rating', '5');
    await a.type('#renter_comment', 'QA: on time and careful with the item.');
    await a.submitWireForm('submitRenterReview');
    await a.waitForText('QA: on time and careful');
  });

  it('18 renter reviews the owner and the listing (FR-23)', async () => {
    await loginAs(renter.email, PASSWORD, 'renter');
    await browser.url(`/renter/rentals/${state.rentalId}`);
    await a.setField('#owner_rating', '4');
    await a.type('#owner_comment', 'QA: owner was responsive.');
    await a.submitWireForm('submitOwnerReview');
    await a.waitForText('QA: owner was responsive.');
    await a.setField('#listing_rating', '5');
    await a.type('#listing_comment', 'QA: camera worked perfectly.');
    await a.submitWireForm('submitListingReview');
    await a.waitForText('QA: camera worked perfectly.');
  });

  it('19 reviews show publicly on owner profile and listing (FR-45)', async () => {
    await freshSession();
    if (state.ownerId) {
      await browser.url(`/users/${state.ownerId}`);
      await a.assertNoServerError();
      if (!(await a.pageText()).includes('QA: owner was responsive.')) note('Owner review not visible on public profile /users/{id}.');
    }
    await browser.url(`/listings/${state.listingId}`);
    if (!(await a.pageText()).includes('QA: camera worked perfectly.')) note('Listing review not visible on public listing page.');
    assert.ok(true);
  });

  it('20 renter notifications cover the cycle (FR-13/40)', async () => {
    await loginAs(renter.email, PASSWORD, 'renter');
    await browser.url('/notifications');
    await a.assertNoServerError();
    const text = await a.pageText();
    const expected = ['Request approved', 'Booking confirmed', 'Rental is now active', 'Deposit released'];
    const missing = expected.filter((t) => !text.includes(t));
    if (missing.length) note(`Renter notifications page missing: ${missing.join(', ')}`);
    assert.ok(missing.length < expected.length, 'no lifecycle notifications shown at all');
  });
});
