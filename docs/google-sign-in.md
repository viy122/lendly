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
2. Create an OAuth client with application type **Web application**. Register **`http://localhost:8000/auth/google/callback`** as an authorized redirect URI, matching scheme, port and path exactly. If your audience is in Testing, add the Gmail accounts you will use as test users.
3. Copy the client ID and secret into the prepared `.env` fields above. The application requests only `openid`, `profile` and `email`.
4. Run `php artisan config:clear`, then restart `php artisan serve` if it is running. **Continue with Google** is the first option on login and registration, with email/password underneath. Until all three Google settings are present, the button is disabled and explains that email is available. Filling the settings enables the existing Google flow.

For a hosted deployment, use its HTTPS `APP_URL`, register that exact callback in Google Cloud, and update `GOOGLE_REDIRECT_URI` if you override the prepared value. Laragon `.test` hostnames may not meet Google's redirect URI rules; use the localhost development URL or a supported HTTPS domain. See [Google's web application OAuth setup](https://developers.google.com/identity/protocols/oauth2/web-server) and [OpenID Connect requirements](https://developers.google.com/identity/openid-connect/openid-connect).

## Account behavior

- Login asks for the renter or owner interface before opening Google. Registration starts in the renter interface, as ordinary registration does. Both interfaces belong to the same member account.
- Google must confirm a verified Gmail address. A new Google member has a confirmed email and continues to the existing ID/selfie step. Google login does not verify a National ID or create an identity badge.
- An existing active member with that verified Gmail can use Google without losing their name, password, listings or rental history. Unverified existing accounts must finish their normal email verification first. Ambiguous legacy Gmail aliases, conflicting Google links, suspended accounts, administrator accounts and temporary authentication lockouts are rejected.
- Returning Google logins preserve the account's profile name and identity verification. If the Google display name is not the full legal name, the identity page links to correcting the profile before verification.
- New Google accounts receive a random, unknown local password. To set a Lendly password for password-protected account actions, sign out and use **Forgot password?** on the login page. The existing email-code recovery flow must have a working mail sender; see [authentication setup](authentication-setup.md). Google sign-in itself does not send an email verification link.
- Changing an account's email removes its Google link. Closing the account removes that identifier along with other personal data. A later signup creates a new account and does not restore closed-account history.

Google sign-in uses a session-bound, single-use OAuth state and rotates the session after authentication. Google access and refresh tokens are not stored. The database migration is `2026_10_21_000001_add_google_id_to_users_table.php`; standard `php artisan migrate` applies it on another installation.

Live Google login requires your actual client credentials and Google Cloud configuration. Automated checks simulate Google responses while exercising the real Socialite state and callback handling.
