// Login/session helpers and fixture ids shared by the component and module specs.
const fs = require('fs');
const path = require('path');
const LoginPage = require('../pages/LoginPage.cjs');
const a = require('./actions.cjs');

const PASSWORD = 'password';

function fixtures() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'artifacts', 'fixtures.json'), 'utf8'));
}

async function freshSession() {
  // Cookies can only be cleared for the domain currently loaded.
  await browser.url('/');
  await browser.deleteCookies();
  await browser.url('/');
}

// iface: 'owner' | 'renter' for members; omit for admin.
async function loginAs(email, iface, password = PASSWORD) {
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

const asAdmin = () => loginAs('admin@tala.test');

module.exports = { PASSWORD, fixtures, freshSession, loginAs, asAdmin };
