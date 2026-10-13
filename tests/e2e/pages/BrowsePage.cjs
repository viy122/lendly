// POM: marketplace browse (/listings). Livewire debounced search.
const { waitForLivewireIdle } = require('../support/livewire.cjs');

class BrowsePage {
  async open() {
    await browser.url('/listings');
  }

  get keywordInput() {
    return $('input[aria-label="Search items"]');
  }

  get listingCards() {
    return $$('a[href*="/listings/"]');
  }

  async search(keyword) {
    const input = await this.keywordInput;
    await input.waitForDisplayed({ timeout: 15000 });
    await input.setValue(keyword);
    await waitForLivewireIdle();
  }
}

module.exports = new BrowsePage();
