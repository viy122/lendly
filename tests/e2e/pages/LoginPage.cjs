// POM: login (/login, Volt). Includes member interface picker step.
const { waitForLivewireIdle } = require('../support/livewire.cjs');

class LoginPage {
  async open() {
    // The Chrome profile is shared across specs; /login redirects signed-in users.
    await browser.url('/');
    await browser.deleteCookies();
    await browser.url('/login');
  }

  get emailInput() {
    return $('#email');
  }

  get passwordInput() {
    return $('#password');
  }

  get submitButton() {
    return $('form[wire\\:submit="login"] button[type="submit"]');
  }

  async loginAs(email, password) {
    await (await this.emailInput).waitForDisplayed({ timeout: 15000 });
    await (await this.emailInput).setValue(email);
    await (await this.passwordInput).setValue(password);
    await (await this.submitButton).click();
    await waitForLivewireIdle();
  }

  async interfacePickerVisible() {
    const picker = await $('x-interface-picker, [data-testid="interface-picker"]');
    try {
      await picker.waitForDisplayed({ timeout: 8000 });
      return true;
    } catch {
      // Fallback: picker renders two buttons with renter/owner choice text.
      const html = await browser.execute(() => document.documentElement.innerHTML);
      return /chooseInterface|active_interface|as Renter|as Owner/i.test(html);
    }
  }
}

module.exports = new LoginPage();
