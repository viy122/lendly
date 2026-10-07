# B.2 item listing and management verification

Verified on 7 October 2026. Current status: **Implemented** for the B.2 listing flow and FR-05/06/07/47; A7's missing historical listing relationship is resolved.

## Implemented behavior

- My Listings provides Add Item, Edit, Pause, Resume and Remove actions. New listings require an inclusive available-from/until window, at least one image and valid latitude/longitude, alongside the existing required details. Zero coordinates are valid. Invalid, reversed or expired windows, missing pins, missing photos and non-image uploads cannot be saved.
- Valid new listings publish immediately. Edits publish immediately without an admin approval gate; an explicitly paused listing remains paused after an edit. Resume validates the same required details before publishing.
- Owners can retain existing photos, add multiple photos and remove/replace photos. Existing-photo removals are staged until save; cancelling or failing validation leaves persisted photos intact. Removing the last photo requires a replacement. Listing and image writes use a database transaction, failed saves clean up newly stored images, and removed files are deleted after a successful save. Photo removal is scoped to the authorized listing.
- The date window appears in My Listings, item details and the rental-request form. The availability calendar marks dates outside the window unavailable. Requested rentals must fit entirely within the window, including both boundary dates; request submission reloads current listing data and owner approval checks the window again.
- Availability edits cannot exclude existing approved bookings. This guard and listing edits lock the listing within the transaction, using the same listing lock as booking approval. Expired windows are excluded from Browse and map results and cannot open a rental-request form.
- Remove soft-deletes the listing while retaining its images and historical records. Rental and request relationships include removed listings. Existing approved agreements remain accessible and can finish without losing the associated listing. Removed items cannot receive new requests or approval of pending requests; bookings, condition records, payments, reviews, disputes and messages remain in retained transaction history.

## Verification results

- Listing, search, request, history, booking notification, paid reservation and listing messaging regression run: **135 tests passed, 1,337 assertions**.
- Final B.2 flow run after the final photo-save guard and expired-window regression were added: **19 tests passed, 111 assertions**. Includes create/edit validation, immediate publication, pause/resume, photo replacement, authorization, inclusive dates, stale-window rejection, Browse/map expiry filtering, approved-booking protection and removal with an existing agreement.
- `npm run build`: passed; updated availability calendar assets generated.
- Project formatting and scoped `git diff --check`: passed.
- Local MySQL migration `2026_10_07_000008_add_listing_availability_window`: applied successfully. Adds two nullable date fields without deleting or inventing existing listing data. Regression tests use isolated in-memory SQLite.

## Compatibility and verification limits

Existing listings with no saved date window retain their previously unrestricted dates until edited. Owners must complete the new required fields before saving an edit or resuming an incomplete paused listing. Existing pending/rejected moderation records are retained; valid owner saves now publish immediately.

Historical listing relations and deletion constraints were already present in the working tree; this follow-up verified them and retained approved-agreement access after listing removal. No production deployment, live browser layout review, real device-location permission prompt or simultaneous database-operation stress test is claimed.
