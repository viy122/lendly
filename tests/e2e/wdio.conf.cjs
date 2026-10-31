// WebDriverIO config — Lendly QA (Selenium via chromedriver, headless Chromium).
// Browser: Chrome by default. Brave override: set BRAVE_BINARY to brave.exe path
// (same Chromium engine, identical flags). No Java needed (chromedriver, not selenium-standalone).
// Every test, pass or fail, saves a screenshot to artifacts/screens/ and a line to
// artifacts/results.jsonl; tests/e2e/report/build-report.cjs turns those into the Word report.
const fs = require('fs');
const path = require('path');
const os = require('os');

const braveBinary = process.env.BRAVE_BINARY || process.env.CHROME_BINARY || undefined;
const baseUrl = process.env.E2E_BASE_URL || 'http://127.0.0.1:8000';
const ARTIFACTS = path.join(__dirname, 'artifacts');
const SCREENS = path.join(ARTIFACTS, 'screens');
const RESULTS = path.join(ARTIFACTS, 'results.jsonl');
const chromeArgs = [
  '--headless=new',
  '--no-sandbox',
  '--disable-dev-shm-usage',
  '--disable-gpu',
  '--window-size=1280,900',
  // Hide Chrome's own internal log noise (GPU, GCM, updater) from the terminal.
  '--log-level=3',
  '--silent',
  `--user-data-dir=${path.join(os.tmpdir(), 'lendly-wdio-profile')}`,
];

// Spec file order = report order: component → module → integrated → smoke.
const specs = ['component', 'module', 'full-cycle', 'smoke.*'].map((name) => path.join(__dirname, 'specs', `${name}.e2e.cjs`));

exports.config = {
  runner: 'local',
  specs,
  maxInstances: 1,
  capabilities: [
    {
      browserName: 'chrome',
      'goog:chromeOptions': {
        ...(braveBinary ? { binary: braveBinary } : {}),
        args: chromeArgs,
        excludeSwitches: ['enable-logging'],
      },
    },
  ],
  logLevel: 'error',
  baseUrl,
  waitforTimeout: 15000,
  connectionRetryTimeout: 60000,
  framework: 'mocha',
  reporters: ['spec'],
  mochaOpts: { ui: 'bdd', timeout: 120000 },
  services: ['chromedriver'],

  onPrepare() {
    fs.rmSync(SCREENS, { recursive: true, force: true });
    fs.mkdirSync(SCREENS, { recursive: true });
    fs.rmSync(RESULTS, { force: true });
  },

  async afterTest(test, context, { passed, error, duration }) {
    const id = (test.title.match(/^([A-Z]+-\d+)/) || [])[1] || test.title.slice(0, 20);
    const file = path.join(SCREENS, `${id}-${passed ? 'PASS' : 'FAIL'}.png`);
    try {
      // A stuck confirm/alert blocks every browser command, including screenshots.
      await browser.acceptAlert().catch(() => {});
      await browser.saveScreenshot(file);
    } catch {
      // Browser may be gone after a hard failure; the result line still records it.
    }
    const entry = {
      id,
      title: test.title,
      suite: test.parent,
      spec: path.basename(test.file || ''),
      result: passed ? 'PASS' : 'FAIL',
      ms: duration,
      error: error ? String(error.message || error).split('\n')[0].slice(0, 300) : null,
      screenshot: fs.existsSync(file) ? path.relative(ARTIFACTS, file).replace(/\\/g, '/') : null,
      at: new Date().toISOString(),
    };
    fs.appendFileSync(RESULTS, JSON.stringify(entry) + '\n');
  },
};
