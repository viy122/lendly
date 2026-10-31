// Generic UI actions for Livewire pages. Selectors target wire:* attributes so
// specs survive copy/styling changes.
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..', '..', '..');
const ARTIFACTS = path.join(__dirname, '..', 'artifacts');
const FIXTURE_PNG = path.join(ARTIFACTS, 'fixture.png');

function ensureFixture() {
  fs.mkdirSync(ARTIFACTS, { recursive: true });
  if (!fs.existsSync(FIXTURE_PNG)) {
    // 1x1 PNG: passes Laravel's `image` and `mimes:png` rules.
    fs.writeFileSync(
      FIXTURE_PNG,
      Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64')
    );
  }
  return FIXTURE_PNG;
}

const esc = (s) => s.replace(/:/g, '\\:');

async function pageText() {
  return browser.execute(() => document.body.innerText);
}

async function waitForText(text, timeout = 20000) {
  await browser.waitUntil(async () => (await pageText()).includes(text), {
    timeout,
    timeoutMsg: `Text never appeared: "${text}"`,
  });
}

async function waitForUrl(re, timeout = 20000) {
  await browser.waitUntil(async () => re.test(await browser.getUrl()), {
    timeout,
    timeoutMsg: `URL never matched ${re}`,
  });
  return browser.getUrl();
}

// Fails fast when Laravel/Livewire rendered an error page or error modal.
async function assertNoServerError() {
  const state = await browser.execute(() => ({
    text: document.body.innerText.slice(0, 400),
    modal: !!document.querySelector('#livewire-error'),
  }));
  if (state.modal || /Server Error|Internal Server Error|^\s*(403|404|419|500)\s*\|/m.test(state.text)) {
    throw new Error(`Server error on ${await browser.getUrl()}: ${state.text.slice(0, 200)}`);
  }
}

async function scrollClick(selector) {
  const el = await $(selector);
  await el.waitForExist({ timeout: 20000 });
  await el.scrollIntoView({ block: 'center' });
  await el.waitForClickable({ timeout: 20000 });
  await el.click();
}

// Auto-accept wire:confirm / window.confirm prompts on the current page.
async function autoConfirm() {
  await browser.execute(() => {
    window.confirm = () => true;
  });
}

// Accepts a native confirm/alert if one is open (the window.confirm override can be lost
// when Livewire re-renders between the override and the click).
async function acceptDialogIfOpen() {
  try {
    await browser.acceptAlert();
  } catch {
    // No dialog open.
  }
}

async function clickWire(method) {
  await autoConfirm();
  await scrollClick(`[wire\\:click="${method}"]`);
  await browser.pause(300);
  await acceptDialogIfOpen();
}

async function submitWireForm(method) {
  await scrollClick(`form[wire\\:submit="${method}"] button[type="submit"]`);
}

async function type(selector, value) {
  const el = await $(selector);
  await el.waitForExist({ timeout: 20000 });
  await el.scrollIntoView({ block: 'center' });
  await el.setValue(String(value));
}

// For date/select inputs where keyboard entry is locale-dependent.
async function setField(selector, value) {
  await (await $(selector)).waitForExist({ timeout: 20000 });
  await browser.execute(
    (sel, v) => {
      const el = document.querySelector(sel);
      el.value = v;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    },
    selector,
    String(value)
  );
}

async function upload(selector, file = ensureFixture()) {
  const el = await $(selector);
  await el.waitForExist({ timeout: 20000 });
  await el.setValue(file);
}

async function screenshot(name) {
  fs.mkdirSync(ARTIFACTS, { recursive: true });
  const file = path.join(ARTIFACTS, `${name.replace(/[^a-z0-9-]+/gi, '_')}.png`);
  await browser.saveScreenshot(file);
  return file;
}

// Reads the newest signed verification link written by MAIL_MAILER=log.
// The log omits the recipient, so call this right after the registration.
function latestVerificationLink() {
  const log = fs.readFileSync(path.join(ROOT, 'storage', 'logs', 'laravel.log'), 'utf8');
  const decoded = log.replace(/=\r?\n/g, '').replace(/=3D/g, '=');
  const links = decoded.match(/https?:\/\/[^\s"'<>\]()]+\/verify-email\/\d+\/[a-f0-9]+\?[^\s"'<>\]()]+/g);
  if (!links) throw new Error('No verification link found in laravel.log');
  return links[links.length - 1].replace(/&amp;/g, '&');
}

const ymd = (d) => d.toISOString().slice(0, 10);
const daysFromNow = (n) => {
  const d = new Date();
  d.setDate(d.getDate() + n);
  return ymd(new Date(d.getTime() - d.getTimezoneOffset() * 60000));
};

module.exports = {
  esc, pageText, waitForText, waitForUrl, assertNoServerError, scrollClick, autoConfirm, acceptDialogIfOpen,
  clickWire, submitWireForm, type, setField, upload, screenshot, latestVerificationLink,
  daysFromNow, ensureFixture,
};
