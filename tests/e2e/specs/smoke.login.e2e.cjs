// Smoke: login form renders; seeded member login reaches picker or dashboard.
const assert = require('assert');
const LoginPage = require('../pages/LoginPage.cjs');
const { waitForUrlContains } = require('../support/livewire.cjs');

describe('smoke: login', () => {
  it('renders email + password + submit', async () => {
    await LoginPage.open();
    assert.ok(await (await LoginPage.emailInput).isDisplayed());
    assert.ok(await (await LoginPage.passwordInput).isDisplayed());
    assert.ok(await (await LoginPage.submitButton).isDisplayed());
  });

  it('seeded member login proceeds (picker or dashboard)', async () => {
    const email = process.env.E2E_MEMBER_EMAIL || 'member@tala.test';
    const password = process.env.E2E_MEMBER_PASSWORD || 'password';
    await LoginPage.open();
    await LoginPage.loginAs(email, password);

    // Either the renter/owner interface picker appears (current login flow),
    // or we land on a dashboard directly.
    let progressed = await LoginPage.interfacePickerVisible();
    if (!progressed) {
      try {
        await waitForUrlContains('dashboard', 10000);
        progressed = true;
      } catch {
        progressed = false;
      }
    }
    if (!progressed) {
      // Surface page state for the QA report on failure.
      const url = await browser.getUrl();
      const title = await browser.getTitle();
      assert.fail(`login did not progress. url=${url} title=${title}`);
    }
    assert.ok(progressed);
  });
});
