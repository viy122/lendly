// Written content of the QA report. Test-case rows and screenshots come from the last run;
// everything else (findings, environment, recommendations) is maintained here by hand.
module.exports = {
  title: 'Lendly QA Test Report — Selenium E2E',
  tester: 'Drei (QA)',

  levels: {
    C: 'Each UI component on its own: forms, validation messages, filters, calculators and widgets.',
    M: 'Each business module on its own, from a prepared state, including unhappy paths (decline, cancel, reject, damage, dispute).',
    I: 'All modules together: one rental from sign-up to reviews, exactly as real users would do it.',
    S: 'Quick checks that the key pages open and respond.',
  },

  phpunit: {
    what: "The dev team's existing backend tests (49 files), run on an in-memory database.",
    total: '681',
    passed: '681',
    failed: '0',
    summary: '681 of 681 tests. Two of them need the PHP GD extension, which is disabled in this XAMPP install; with GD enabled for the run, both pass.',
  },

  environment: [
    ['Branch', '`qa/selenium-e2e-setup` (base `main @ 2bff7a1`)'],
    ['Tool', 'WebdriverIO 10 + ChromeDriver 155 (WebDriver protocol, Selenium-compatible), Mocha'],
    ['Browser', 'Chrome 155, headless, 1280×900'],
    ['App', 'Laravel app on `http://127.0.0.1:8000` (XAMPP PHP 8.2)'],
    ['Test data', 'Fresh throwaway SQLite DB per run (`database/e2e.sqlite`), seeded with test accounts, categories and module scenarios. The real MySQL database is never touched.'],
    ['Requirements baseline', 'FR-01 to FR-53 (`FUNCTIONAL_REQUIREMENTS_RECHECK.md`)'],
  ],

  findingsLead: 'No blocking or high-severity defects were found. Four low-severity issues need a dev decision.',
  findings: [
    ['D-01', 'Email address is silently rewritten at sign-up', 'Register as qa.owner.x@gmail.com, then view the account as admin.', 'Address shown as typed, or the user is told it is shortened', 'Shown as qaownerx@gmail.com: dots and +tags are stripped (AuthEmail::normalize) with no notice', 'Low'],
    ['D-02', 'Map pin is not checked against the typed location', 'Type "Nasugbu, Batangas", click the map without panning, publish.', 'Warning when the pin is far from the typed location', 'Publishes with the pin at the default centre (Manila), about 70 km away', 'Low'],
    ['D-03', 'Duplicate "pin" error on the listing form', 'Submit the listing form without a map pin (test C-09; see its screenshot).', 'One "Place a pin on the map" message', 'The message appears twice in the error summary (once for latitude, once for longitude)', 'Low'],
    ['D-04', 'Admin cannot give a reason when taking down a live listing', 'Admin › Listings › live listing (test M-24).', 'Admin can explain the takedown to the owner', 'Live listings only offer "Remove" (no reason, owner not told why). "Reject with reason" exists but only for "pending approval" listings, and owner listings never enter that state because they publish immediately.', 'Low'],
  ],
  envNotes: [
    'PHP GD extension is disabled in XAMPP (`;extension=gd` in php.ini); two backend tests need it.',
    '`public/storage` is not linked on this machine (`php artisan storage:link`), so uploaded photos show as broken images locally.',
    'ChromeDriver must match the installed Chrome version. Chrome auto-updated from 153 to 155 during this round; ChromeDriver was updated to 155.',
    'Test I-11 failed once (out of several runs) because the browser\'s "Confirm receipt?" pop-up was not auto-accepted in time. This was a test-script timing issue, not an app defect; the tests now accept the pop-up directly, and the next full run passed 66/66.',
    'A leftover `public/hot` file (from a Vite dev server that did not shut down cleanly) makes pages load without CSS/JS. The test script now removes it automatically.',
  ],

  recommendations: [
    ['Fix', [
      'D-01: tell users at sign-up that Gmail dots and +tags are ignored, or keep the typed address for display.',
      'D-02: warn when the pin is far from the typed location, or fill the location text from the pin.',
      'D-03: show the map-pin error once.',
      'D-04: let admins add a reason when removing a live listing and notify the owner; or remove the unused "pending approval" reject path.',
    ]],
    ['Add', [
      'Stable `data-testid` attributes on key buttons so tests do not depend on internal names.',
      '`php artisan storage:link` and enabling GD in the README setup steps.',
      'Align wording with the requirements: the app says "Active Rental" where FR-44 says "Ongoing".',
    ]],
    ['Still not covered', [
      'Overdue and due-soon notifications (FR-19, FR-20): need a way to move the clock forward in tests.',
      'Map page pins (FR-48, FR-53), phone-width layout, and the real Brave browser.',
    ]],
  ],

  howToRun: {
    lead: 'Everything runs with one command from Git Bash in the project folder (Node, Chrome and XAMPP PHP are needed; MySQL is not).',
    steps: [
      'One-time setup: `npm install`.',
      'Run the tests: `npm run test:e2e` (about 7 minutes). In VS Code: Ctrl+Shift+P › Run Test Task.',
      'Rebuild this report from the last run: `npm run test:e2e:report`.',
    ],
    commands: [
      'npm run test:e2e',
      'npm run test:e2e -- --spec tests/e2e/specs/module.e2e.cjs   # one level only',
      'npm run test:e2e:report',
    ],
    after: 'Each run prints ✓/✖ per test in the terminal and saves one screenshot per test in `tests/e2e/artifacts/screens/` (named by test ID and result).',
  },
};
