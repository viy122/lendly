# Lendly Selenium Full Regression — Test Report Template

> Stack: WebDriverIO (JS/TS) + Brave (Chromium) + Local dev (`http://localhost:8000`)
> Fill up after every run. Isang file per run: kopyahin ito bilang `YYYY-MM-DD-run-N.md`.

---

## 1. Run Info (fill up)

- Date / Time:
- Tester (QA):
- Branch / Commit:
- Env: [ ] local `php artisan serve :8000` + `npm run dev` / DB: `lendly_e2e` / Seeder: `TestAccountSeeder + BulkDemoSeeder`
- Base URL:
- Brave version + path (hal. `C:\Program Files\BraveSoftware\Brave-Browser\brave.exe`):
- Chromedriver version:
- Suite na tinakbo: [ ] smoke [ ] full [ ] booking-only [ ] admin-only
- Command: `npx wdio run tests/e2e/wdio.conf.ts --suite full`

## 2. Summary (numbers lang)

| Suite | Passed | Failed | Skipped | KnownDefect |
|---|---|---|---|---|
| auth (register/login/picker/lockout/verify) | / | / | / | / |
| marketplace (browse/search/show/map) | / | / | / | / |
| owner (CRUD listing + approve/reject) | / | / | / | / |
| renter booking (request→agreement→pay→pickup/return) | / | / | / | / |
| payments + proof | / | / | / | / |
| messages + notifications | / | / | / | / |
| admin (moderate/suspend/dispute) | / | / | / | / |
| profiles + reviews | / | / | / | / |
| **TOTAL** | / | / | / | / |

- Link sa Allure/spec report:
- Video/screenshot folder:

## 3. Critical E2E (dapat green bago mag-Go)

| # | Flow | Result | Notes / Rental ID |
|---|---|---|---|
| 1 | member login → interface picker → renter.dashboard | PASS / FAIL | |
| 2 | member login → interface picker → owner.dashboard | PASS / FAIL | |
| 3 | browse → search → listing detail → request form | PASS / FAIL | |
| 4 | owner approve → agreement (both timestamps) → rental created | PASS / FAIL | |
| 5 | renter offline pay + proof → payment-proofs view | PASS / FAIL | |
| 6 | dual pickup → Active → dual return → inspection → Completed | PASS / FAIL | |
| 7 | review both sides → public profile `users/{id}` | PASS / FAIL | |
| 8 | chat `rental-requests/{id}/chat` + notifications bell | PASS / FAIL | |
| 9 | admin approve listing + suspend/reactivate + resolve dispute | PASS / FAIL | |
| 10 | cancel w/ 48h-20% fee display + reason persisted | PASS / FAIL | |

## 4. Failed / Defect Log (kopyahin per bug)

### DEF-001
- Title:
- Suite/spec + step:
- Severity: [ ] Blocker [ ] Critical [ ] Major [ ] Minor
- FR link (hal. FR-11, FR-17, NFR-01):
- Steps to reproduce:
  1.
  2.
  3.
- Expected:
- Actual:
- Test data (user/listing/rental ID):
- Evidence (screenshot/video path):
- Brave console error (kung meron):
- Reproducible: [ ] always [ ] sometimes [ ] once

### DEF-002 (kopya lang ng taas)
- Title:
- Suite/spec + step:
- Severity:
- FR link:
- Steps / Expected / Actual:
- Test data / Evidence:

## 5. Known-Defect Watchlist (galing audit 2026-10-09 — huwag i-close, i-verify lang)

| Audit # | Check | Result ngayon |
|---|---|---|
| 1 | map popup hindi nag-eexecute ng JS (`map.js:106`) | PASS / FAIL (XSS) |
| 2 | owner hindi makapag-submit ng self-review (dapat 403) | PASS / FAIL |
| 3 | walang double rental sa overlapping approve | PASS / FAIL |
| 4 | walang double payment / partial payment+deposit | PASS / FAIL |
| 5 | delete account hindi nabubura history ng kabilang party | PASS / FAIL |
| 6 | removed listing hindi nag-500 sa history | PASS / FAIL |
| 7 | cancel blocked pagkatapos ng handover timestamp | PASS / FAIL |
| 8 | stale request form recheck ng availability | PASS / FAIL |
| 9 | per-login role choice enforced | PASS / FAIL |

## 6. FR Traceability (lagyan ng check)

- FR-01 register:
- FR-03 role choice:
- FR-09 search / FR-10 detail / FR-48-53 map:
- FR-11 request / FR-12 approve / FR-14 rental create / FR-15 terms:
- FR-17 receipt both parties / FR-41 simulated pay:
- FR-18 fulfillment / FR-43-44 pickup:
- FR-19 status display / FR-20 overdue+reminder:
- FR-21-22 return/inspection / FR-34-37 cancel:
- FR-23 reviews / FR-45 public profile:
- FR-24 history preservation:
- FR-26-28 admin / FR-29-30 dispute:
- FR-31-33 messages / FR-13/40 notifications both parties:

## 7. Verdict (bilugan isa)

- [ ] **GO** — lahat critical green, walang Blocker/Critical.
- [ ] **CONDITIONAL GO** — green critical, minor/known-defect lang + workaround nakasulat.
- [ ] **NO GO** — may Blocker/Critical (data-loss, payment mismatch, XSS, auth bypass).

Justification (2-3 lines):

## 8. Next Steps

- [ ] Defects na-file (link):
- [ ] Re-run date:
- [ ] Kailangang ayusin bago mag-staging:
- [ ] Not covered ngayon (hal. real inbox, geolocation permission, scheduler prod):

---

### Paano gamitin
1. `cp docs/qa/lendly-selenium-report-template.md docs/qa/2026-10-12-run-1.md`
2. Fill up sections 1-2 habang tumatakbo ang WDIO.
3. Bawat fail → duplicate `DEF-00X` block agad, attach screenshot path.
4. Tapos → verdict + next steps, send link sa team.
