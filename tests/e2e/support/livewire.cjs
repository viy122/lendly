// Livewire-aware wait helpers. Livewire swaps DOM via AJAX; never use fixed pauses.
async function waitForLivewireIdle(timeout = 15000) {
  await browser.waitUntil(
    async () => {
      const busy = await browser.execute(() => {
        const loading = document.querySelectorAll('[wire\\:loading]');
        return Array.from(loading).some((el) => {
          const style = window.getComputedStyle(el);
          return style.display !== 'none' && el.getAttribute('aria-hidden') !== 'true';
        });
      });
      return busy === false;
    },
    { timeout, timeoutMsg: 'Livewire did not go idle in time' }
  );
}

// Wait for Livewire navigate (no full reload): poll URL change.
async function waitForUrlContains(fragment, timeout = 15000) {
  await browser.waitUntil(
    async () => (await browser.getUrl()).includes(fragment),
    { timeout, timeoutMsg: `URL never contained "${fragment}"` }
  );
}

module.exports = { waitForLivewireIdle, waitForUrlContains };
