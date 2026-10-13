// POM: public landing (/). Verifies app boots and renders.
class WelcomePage {
  async open() {
    await browser.url('/');
  }

  async heading() {
    return $('h1');
  }
}

module.exports = new WelcomePage();
