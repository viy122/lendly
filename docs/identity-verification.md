# Member identity verification

Registration follows three steps: account details, email confirmation, then National ID and live-selfie verification. Confirming a member's email opens `/identity/verify` before an intended dashboard or account page. Members can also return to verification from Profile. Administrators keep their existing email-confirmation destination.

A member uses the same identity verification when renting and when listing items. Email verification remains separate. The signup handoff does not impose a new verification requirement on existing rental or listing actions. Without provider configuration, the final step explains that the account and email are ready while identity verification is still pending; it does not collect ID images or grant a badge.

## What is checked

The browser decodes the National ID QR locally. With the member's explicit consent, the official eVerify liveness SDK captures a live selfie and returns a session identifier. Laravel sends the QR value and that session identifier to the documented National ID **QR Verify** endpoint, then checks the authenticated name components and birth date against the submitted details and account name.

The client cannot grant verification. Reading a QR, completing a selfie capture, returning HTTP 200, or opening the public National ID checker is not sufficient. Only the documented matched result and matching details can set the server-side verification state.

The member must use the full name on their National ID. Name matching ignores case and extra whitespace, but does not guess alternative names, remove accents, or apply fuzzy matching. Correct an account-name error in Profile before retrying.

## Configure the official connection

Obtain approved credentials and the assigned API base URL from the provider. Use `.env`, never a source file or browser bundle:

```dotenv
IDENTITY_ENVIRONMENT=sandbox
IDENTITY_BASE_URL=https://your-assigned-provider-base-url
IDENTITY_CLIENT_ID=your-server-client-id
IDENTITY_CLIENT_SECRET=your-server-client-secret
IDENTITY_PUBLIC_KEY=your-sdk-public-key
IDENTITY_SDK_URL=https://hackathon-everify-face-liveness.e.gov.ph/js/everify-liveness-sdk.min.js
```

The default SDK URL is the **staging SDK documented by DICT**. Production requires the provider's assigned production API URL, production credentials and production SDK URL; do not relabel staging credentials as production. The SDK public key may be sent to the browser. The client ID, client secret and API access token remain server-side.

After changing configuration, clear/rebuild the application's configuration cache as appropriate. The app never guesses the assigned API base URL. Without a complete valid configuration it shows an unavailable state and does not collect identity details or start a selfie session.

Apply the migration during deployment:

```powershell
php artisan migrate
npm install
npm run build
```

Use HTTPS for a deployed verification page. Browser camera access is restricted to secure contexts; localhost is suitable for local development. Camera capture starts only after the member grants consent and explicitly chooses the camera action. QR image uploads are decoded in the browser, limited in size, and are not uploaded to Lendly.

## Sandbox and production

Sandbox success is shown privately as **Test identity check completed**. It does not earn a public Identity verified badge. Only a production result can display that badge. Neither mode has a mock success switch or an administrator override.

Use synthetic test identities supplied by the provider in its sandbox. Local automated tests fake the HTTP boundary and do not contact the registry or submit a real ID or selfie.

## Data handling and invalidation

- Lendly does not store ID images, selfies, QR contents, birth dates, returned demographic profiles or face-image URLs. The member necessarily supplies the QR and personal details during their authenticated verification request; keep production request-body logging disabled for this page and its Livewire requests.
- Attempts store a keyed input fingerprint, expiry and status. A one-way liveness-session hash prevents the same session from being replayed. No raw liveness session is saved.
- The provider reference is encrypted at rest and hidden from User serialization. Public profiles receive only the production verification state.
- Changing an account name or email, account suspension, or account deletion clears verification and cancels pending attempts. Results arriving after cancellation, expiry, profile changes or deletion cannot restore it.
- A successful identity check concerns the matched identity at the time of verification. It is not a background check, a guarantee of rental performance, or a guarantee that a later account session belongs to the same person.

## Official references

- [DICT eVerify API reference](https://platforms.e.gov.ph/api-catalogs/everify): `/api/auth`, `/api/query/qr`, matched and face-mismatch responses.
- [Official liveness SDK integration](https://platforms.e.gov.ph/api-catalogs/everify?tab=integration): `window.eKYC().start({ pubKey })`, returning `result.session_id`.
- [PSA public QR checker](https://everify.gov.ph/check): a separate public tool; its browser result does not automatically verify an account in this app.

Live provider integration cannot be exercised until the assigned credentials, environment and allowed origins are configured. Verify the assigned deployment's current contract against these documented fields during onboarding; unknown response shapes fail closed.
