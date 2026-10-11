# B.3 item search and browsing verification

Verified on 7 October 2026. Current status: **Implemented** for FR-09, FR-10 and FR-48–53; the A6 zero-coordinate defect is resolved.

## Implemented behavior

- Browse supports keyword, category/subcategory, daily price range, condition, brand, and city/area text. Location matches part of the listing address, trims surrounding whitespace, combines with the other filters, persists in the URL, resets pagination, and clears with the filters. Blank location works without device-location permission. A zero maximum price is applied as a real bound.
- Available published listings with coordinates appear on the interactive map. Map keyword/category predicates match Browse, including description, brand, and subcategory matches.
- Explicit browser location capture centers the map and marks the selected position. Both coordinates are submitted together. Denial, timeout/unavailability, and unsupported browsers show actionable messages while keeping search usable. Repeated clicks do not start concurrent location requests.
- With both coordinates present, 5/10/25/50 km radii use great-circle distance and results sort nearest to farthest by unrounded distance, with consistent ties. Zero latitude, zero longitude, and the origin are valid. Incomplete or cleared coordinates disable distance filtering and sorting.
- Pin previews include the first ordered photo (or a missing/broken-photo fallback), name, daily price, and distance when set. A single pointer click or Enter on the pin opens the detail page.
- Item details retain photos, description, rates, owner rating, and the availability calendar, including approved holds and paid reservation dates.

## Verification results

- `php artisan test --compact tests/Feature/ListingSearchTest.php tests/Unit/GeoTest.php tests/Feature/PaidReservationTest.php tests/Feature/PaymentReservationTest.php`: **58 tests passed, 580 assertions**. SQLite test data is isolated in memory.
- `npm run test:map`: **5 tests passed**, using simulated browser location responses. Covers explicit capture, zero coordinates, atomic coordinate updates, denial/unavailability/timeout, unsupported browsers, and successful retry.
- `npm run build`: passed.
- PHP syntax and `git diff --check`: passed.
- Local browser review: Browse returned two listings for “Lipa” and persisted `location=Lipa` in the URL. Keyboard focus exposed the pin preview, Enter opened `/listings/1`, and a single pointer click opened `/listings/2`. Detail pages displayed description, daily rate, owner rating, and the October availability calendar.

## Verification limits

Actual operating-system location permission prompts and real device coordinates were not exercised; location success/error cases use simulated responses. Existing demo listing photo URLs were unavailable during browser review, so the map's broken-photo fallback was verified there. Marker photo URL/ordering is covered by the feature tests. No production acceptance or performance benchmark is claimed.
