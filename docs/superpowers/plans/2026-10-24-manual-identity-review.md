# Manual Identity Review Implementation Plan

> **For agentic workers:** Use superpowers:subagent-driven-development to implement the independently owned tasks below.

**Goal:** Let members submit a National ID and selfie, calculate a local face similarity score, and require administrator approval before new rentals or listings.

**Architecture:** Private uploads and review metadata live in a separate IdentitySubmission. OpenCV YuNet and SFace run locally through a Python subprocess; no images or embeddings are sent to a face service. An administrator checks the official public QR checker, account details and face photos before approving the member.

**Tech Stack:** Laravel 12, Livewire 3, private local storage, Python 3.10+, OpenCV headless 4.x.

**Spec:** The user's decisions in this chat: automated face matching now; browse while pending; approval required to rent or list. The following defaults complete that design.

## Global Constraints

- JPEG, PNG or WebP images, at most 8 MB each. ID front and selfie required; back optional for a QR printed there.
- Explicit processing consent; only the submitting member and active verified administrators can read pending documents.
- Raw cosine similarity in [-1, 1] is a review aid, never a confidence percentage or automatic approval.
- A selfie photo or camera capture does not establish liveness. Manual approval is labelled ID reviewed, not government authentication.
- Delete private photos after decision/cancellation; expire unreviewed submissions after 30 days. Preserve minimal review metadata.
- Name/email changes, suspension or deletion invalidate approval and pending submissions.
- Block new rental requests and listing creation/publication in shared server services. Keep browsing and existing rental obligations available.
- Preserve earlier Google sign-in fixes and unrelated uncommitted work. No external deployment or real ID upload during development.

## Review Focus

- Forged Livewire calls or direct services must not bypass approval, administrator checks or private document access.
- Profile changes between submission and review must prevent stale approval.
- File/ML failures must remove incomplete uploads and never create an approval.
- Two concurrent reviews or submissions must not yield conflicting decisions.
- Low scores, multiple selfie faces and unavailable models must not be presented as successful verification.

### Task 1: Private submissions and administrator decisions

**Files:** IdentitySubmission model and additive migration; ManualIdentityReview service; IdentityDocumentController; User lifecycle; ManualIdentityReviewTest.

**Interfaces:** submit(User, UploadedFile front, ?UploadedFile back, UploadedFile selfie, bool consent): IdentitySubmission. review(User admin, int id, string decision, string notes, bool qrChecked, bool detailsMatch, bool faceReviewed): IdentitySubmission. FaceComparison::compare(string frontPath, string selfiePath): float.

- [x] Add failing tests for permissions, validation, stale/duplicate decisions and cleanup.
- [x] Implement pending/approved/rejected/cancelled submissions, locked fresh account checks and after-commit deletion.
- [x] Run the focused tests serially; review model hooks and file access.

### Task 2: Member and administrator screens

**Files:** Identity/Verify component and view; Admin/IdentitySubmissions/Index and view; routes; sidebar; onboarding and identity badge copy.

- [x] Replace unavailable integration screen with private submission and pending/rejected/approved states.
- [x] Add administrator queue, private photo views, raw score and required QR/details/face confirmations.
- [x] Test member/admin actions, resubmission, unauthorised calls and rendering.

### Task 3: Rental and listing access

**Files:** RentalRequestSubmission; ListingPublication and owner form; relevant listing/cart views; explicit verified-identity test fixtures.

- [x] Add failing service and UI bypass tests for pending members and owners.
- [x] Apply approval checks to fresh locked users at the shared boundaries; show a link to identity review.
- [x] Verify approved flows and existing rental obligations still work.

### Task 4: Local face comparison and integration

**Files:** scripts/face-match; FaceComparison service; services config; environment example; setup/identity documentation; face tests; existing auth/identity tests.

- [x] Test process errors and malformed or nonfinite similarity output.
- [x] Pin OpenCV and official model hashes, provide local setup, and install in the private workspace runtime.
- [x] Execute real model inference with non-sensitive fixture photos and no-face/multiple-face cases.
- [x] Restore mandatory identity onboarding expectations and replace obsolete SDK UI tests with manual workflow tests.
- [x] Run scoped tests, broader regression tests, build, formatting and a final security review.

## Execution notes

- Ruling: use an uploaded selfie with optional camera capture; do not promise an anti-spoofing/liveness system the project does not have.
- Ruling: an administrator can approve after reviewing the score and photos; no uncalibrated benchmark threshold denies or approves members automatically.
- Ruling: keep the existing official API backend dormant for compatibility, but remove it from the active member screen.

- Verification: 296 affected regression and identity tests passed (2,166 assertions); 9 face-boundary tests passed separately; frontend build and local model smoke checks passed. Earlier broad run: 974 tests, with failures confined to 13 transaction fixture classes subsequently corrected and included in the passing affected run.
- Live browser: replaced the unavailable panel with the new ID-review form in the member's existing authenticated tab; no real ID uploaded and no camera permission requested.
- Review: checked fresh authorization, private image paths/headers, transaction lock order, profile invalidation, expiry, malformed ML outputs and server-level rental/listing gates.
