// WebDriverIO config — Lendly QA (Selenium via chromedriver, headless Chromium).
// Browser: Chrome by default. Brave override: set BRAVE_BINARY to brave.exe path
// (same Chromium engine, identical flags). No Java needed (chromedriver, not selenium-standalone).
const path = require('path');
const os = require('os');

const braveBinary = process.env.BRAVE_BINARY || process.env.CHROME_BINARY || undefined;
const baseUrl = process.env.E2E_BASE_URL || 'http://127.0.0.1:8000';
const chromeArgs = [
  '--headless=new',
  '--no-sandbox',
  '--disable-dev-shm-usage',
  '--disable-gpu',
  '--window-size=1280,900',
  `--user-data-dir=${path.join(os.tmpdir(), 'lendly-wdio-profile')}`,
];

exports.config = {
  runner: 'local',
  specs: [path.join(__dirname, 'specs', '*.e2e.cjs')],
  maxInstances: 1,
  capabilities: [
    {
      browserName: 'chrome',
      'goog:chromeOptions': {
        ...(braveBinary ? { binary: braveBinary } : {}),
        args: chromeArgs,
      },
    },
  ],
  logLevel: 'warn',
  baseUrl,
  waitforTimeout: 15000,
  connectionRetryTimeout: 60000,
  framework: 'mocha',
  reporters: ['spec'],
  mochaOpts: { ui: 'bdd', timeout: 90000 },
  services: ['chromedriver'],
};
