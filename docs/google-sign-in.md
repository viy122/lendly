# Google sign-in setup

The application is prepared for Google sign-in. Fill in the two blank credentials in your existing `.env`:

```dotenv
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Your current local callback is **`http://localhost:8000/auth/google/callback`**. Keep the prepared redirect value unless your app URL changes. These credentials are OAuth credentials from Google Cloud, not a Gmail SMTP app password. Never put them in frontend JavaScript or commit `.env`.

## Create the Google credentials

1. Open [Google Auth Platform](https://console.cloud.google.com/auth/overview) in your Google Cloud project. Configure its app branding, support email and audience.
2. Create an OAuth client with application type **Web application**. Register **`http://localhost:8000/auth/google/callback`** as an authorized redirect URI, matching scheme, port and path exactly. If your audience is in Testing, add the Google accounts you will use as test users.
3. Copy the client ID and secret into the prepared `.env` fields above. The application requests only `openid`, `profile` and `email`.
4. Run `php artisan config:clear`, then restart `php artisan serve` if it is running. **Continue with Google** is the first option on login and registration, with email/password underneath. Until all three Google settings are present, the button is disabled and explains that email is available. Filling the settings enables the existing Google flow.

For a hosted deployment, use its HTTPS `APP_URL`, register that exact callback in Google Cloud, and update `GOOGLE_REDIRECT_URI` if you override the prepared value. Laragon `.test` hostnames may not meet Google's redirect URI rules; use the localhost development URL or a supported HTTPS domain. See [Google's web application OAuth setup](https://developers.google.com/identity/protocols/oauth2/web-server) and [OpenID Connect requirements](https://developers.google.com/identity/openid-connect/openid-connect).

## Account behavior

- Both login and registration open Google first. Registration explicitly requests Google's account chooser and permission screen (`prompt=select_account consent`); ordinary login requests the account chooser and lets Google reuse previously granted consent. Both request only basic OpenID, profile and email scopes. After successful Google sign-in, the member chooses the renter or owner interface. Both interfaces belong to the same account; closing the picker signs out.
- Google must confirm a verified personal Gmail address or a managed Google Workspace school/work account. Workspace profiles must include Google's hosted-domain (`hd`) claim; unmanaged third-party email accounts are rejected, following [Google's authoritative-email guidance](https://developers.google.com/identity/gsi/web/guides/verify-google-id-token). A new Google member has a confirmed email and continues to the existing ID/selfie step. Google login does not verify a National ID or create an identity badge. Email/password registration still requires a personal Gmail address.
- Google sign-in automatically creates an account for a new member or reuses the existing account with that email, preserving its name, listings and rental history. Google also confirms an existing unverified account's email. That account's old password, remembered logins, sessions and reset codes are invalidated; it can set a new password through **Forgot password?** afterward. Already verified accounts keep their password. Gmail dots and plus tags are normalized; Workspace addresses match case-insensitively without removing dots or plus tags. Ambiguous legacy Gmail aliases, conflicting Google links, suspended accounts, administrator accounts and temporary authentication lockouts are rejected.
- Returning Google logins preserve the account's profile name and identity verification. If the Google display name is not the full legal name, the identity page links to correcting the profile before verification.
- New Google accounts receive a random, unknown local password. To set a Lendly password for password-protected account actions, sign out and use **Forgot password?** on the login page. The existing email-code recovery flow must have a working mail sender; see [authentication setup](authentication-setup.md). Google sign-in itself does not send an email verification link.
- Changing an account's email removes its Google link. Closing the account removes that identifier along with other personal data. A later signup creates a new account and does not restore closed-account history.

Google sign-in uses a session-bound, single-use OAuth state and rotates the session after authentication. Google access and refresh tokens are not stored. The database migration is `2026_10_21_000001_add_google_id_to_users_table.php`; standard `php artisan migrate` applies it on another installation.

Google-linked sessions must carry the credential snapshot recorded at login. Sessions from before an account was confirmed, or saved by an old-password login already in progress, require sign-in again after the credentials change. Existing Google-linked sessions created before this snapshot check was deployed also require one fresh sign-in.

Sign-in failures log only the processing stage, exception class and exception code. Provider responses, OAuth tokens, profile details and exception messages are excluded from these diagnostics.

Live Google login requires your actual client credentials and Google Cloud configuration. Automated checks simulate Google responses while exercising the real Socialite state and callback handling.
