# Rental cart and request queue

Renters open a listing's rental form, choose dates and pickup or delivery, then select **Add to cart**. The existing **Send rental request** action remains available for a single item. The renter sidebar and header cart icon open **My Cart** at `/renter/cart`.

The cart groups items by owner. Each listing represents one physical item, so adding it again updates its existing draft. Renters can edit and save dates or handover, remove items, select individual items or an owner's group, review charges and rules, and send the selection as rental requests. A cart holds up to 50 drafts. It persists in the database for the signed-in member and is removed when that account closes.

Adding a draft sends no notifications and reserves no dates. Sending the selection creates one pending request per item and notifies both participants. The selected batch is all-or-nothing: invalid dates, unavailable listings, or another failure leave the drafts in the cart. Successful checkout consumes only the selected drafts; retrying their IDs cannot send them again. Prices, deposits, rules and platform rates are taken from the server. Changed quotes are saved for review and require fresh acceptance before checkout can proceed.

Owners see pending requests in arrival order, oldest first, with request ID as the tie breaker and submission times displayed in Philippine time. Arrival means the request submission time, not when it entered a cart. Owners can choose any eligible request. Existing approval rules reserve its dates and reject other overlapping pending requests. Other history filters remain newest first.

Each approved item keeps its existing bilateral agreement and booking payment flow. There is no payment at cart checkout, combined owner payment, quantity control, automatic approval or automatic waitlist promotion.

Migration: `2026_10_20_000003_create_cart_items_table.php`. Shared submission checks live in `RentalRequestSubmission`; direct requests and cart checkout use the same availability and pricing validation.
