// Smoke: marketplace browse renders and Livewire search responds.
const assert = require('assert');
const BrowsePage = require('../pages/BrowsePage.cjs');

describe('smoke: browse', () => {
  it('loads /listings with search input', async () => {
    await BrowsePage.open();
    const input = await BrowsePage.keywordInput;
    await input.waitForDisplayed({ timeout: 15000 });
    assert.ok(await input.isDisplayed());
  });

  it('debounced search does not crash the component', async () => {
    await BrowsePage.open();
    await BrowsePage.search('camera');
    // Component must still be present after Livewire round-trip.
    const input = await BrowsePage.keywordInput;
    assert.ok(await input.isDisplayed());
  });
});
