# Authentication and Gmail setup

New accounts require a valid `@gmail.com` address and must open the verification link before renting, listing items, or entering a dashboard. Gmail dots, plus tags, letter case, and existing Googlemail aliases refer to the same mailbox for duplicate checks. Existing account records are preserved. Email verification proves access to a mailbox; this does not verify a person's identity or prevent someone from using multiple Gmail accounts.

Passwords require at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character. Signup, password reset, and profile password changes share the server rule. New password fields display a Weak/Medium/Strong meter and a checklist. Password inputs block copy, cut, paste, and dropping text in the browser; browser controls can be bypassed, so server validation remains mandatory.

Forgot password sends a random 6-digit code. Only its hash is stored in `password_reset_tokens`. It expires exactly 10 minutes after creation, works once, and is replaced by a new request. Requests are limited to once a minute per mailbox and five a minute per IP. Unknown addresses receive the same normal response. Three wrong passwords or reset codes within a 15-minute window lock authentication for a full 15 minutes starting at the third failure. Gmail aliases and a changed IP do not bypass that lock. Correct login, password confirmation, or reset clears previous failures. Requesting another code does not clear failures. Password recovery preserves any administrative suspension and invalidates remembered login tokens and database sessions.

## Configure a Gmail sender locally

Email currently uses `MAIL_MAILER=log`, which writes messages to `storage/logs/laravel.log` and does not deliver them. Choose the Gmail account that will send the system's verification and recovery emails. Enable Google 2-Step Verification and generate an app password for this project. Use the app password, not the Google account's ordinary password. Some managed or protected accounts do not offer app passwords. [Google's app-password instructions](https://support.google.com/accounts/answer/185833?hl=en).

In your local `.env`, set the following values, replacing both example addresses and the password. Keep the app password private and never commit `.env`.

```dotenv
APP_URL=http://localhost:8000
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-sender@gmail.com
MAIL_PASSWORD="your16characterapppassword"
MAIL_FROM_ADDRESS=your-sender@gmail.com
MAIL_FROM_NAME="Lendly"
```

Port 587 uses STARTTLS with authentication. [Google's SMTP settings](https://support.google.com/mail/answer/7104828?hl=en). Use the actual address where you access this app for `APP_URL`, because verification emails contain a signed link to that address. For hosted use, set the public HTTPS URL.

Run `php artisan config:clear` after updating `.env`, and restart long-running development processes. Keep `CACHE_STORE=database` (or another shared persistent cache) so attempts and temporary locks survive requests. Apply normal migrations with `php artisan migrate` if this is a new installation; this authentication change adds no migrations and does not alter existing account data.

To check delivery, register with a Gmail address you control, open the verification email, log out, and request a password reset code. Check Inbox and Spam. Enter the code and a compliant confirmed password, then log in with the new password. SMTP failures show a retry message; signup retains the unverified account so its verification email can be resent. Until SMTP is configured, local log messages are for development only.

## Automated checks on this Windows installation

The installed PHP has SQLite extensions available but disabled by default. These flags enable them only for the test process:

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit
node --test tests/password-strength.test.js
npm run build
```

The PHP suite uses an in-memory test database and a fake/array mail transport; it does not send real email. Run one PHP test process at a time, or set a separate `VIEW_COMPILED_PATH` for each process to avoid Windows compiled-view file races. Existing demo account switching is limited to an authenticated administrator in a local environment and respects the target's verification, suspension, and authentication lock.
