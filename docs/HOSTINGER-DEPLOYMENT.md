# ChachaPrime — Hostinger deployment runbook

This runbook is for a later, deliberate deployment of the Laravel app to `chachaprime.com`. It does not authorize deployment by itself. Keep the existing site and database untouched until a verified backup exists and the target database has been confirmed.

## Before connecting over SSH

- Confirm the target site, document root, PHP 8.4 runtime, and the exact MySQL database name in Hostinger hPanel.
- Export a database backup and archive the current document root before changing either.
- Confirm the GitHub default branch is `main` and review the latest green Laravel CI run.
- Rotate credentials previously pasted into chat or tickets. Use different passwords for MySQL, SMTP mailboxes, and the admin account.
- Never paste production secrets into chat or commit them to Git.

## Application configuration

- Keep the Laravel application code and `.env` outside the publicly served directory where the hosting layout permits.
- Configure `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL=https://chachaprime.com`.
- Set the production MySQL host, database, username, and password using the values shown in hPanel. Do not reuse a mailbox password.
- Configure SMTP using the actual Hostinger SMTP hostname, port, encryption, sender address, and mailbox credentials shown in hPanel. IMAP settings are not SMTP settings.
- Start with Stripe test credentials and a test webhook signing secret. Keep `STRIPE_SECRET_KEY` and `STRIPE_WEBHOOK_SECRET` in the private server environment only.
- Add the optional Gemini API key only if AI product-copy drafting is required.
- Ensure `storage/` and `bootstrap/cache/` are writable by the PHP process without making the entire project world-writable.

## Safe deployment order

1. Take and verify backups of the current website files and the target database.
2. Deploy the repository to a private application directory; do not delete or overwrite the current `public_html` as a shortcut.
3. Install Composer dependencies using the PHP 8.4 runtime and production options appropriate to the environment.
4. Create the private `.env` from `.env.example`, fill it with server-side values, and verify the database name twice.
5. Run `php artisan about` and a database connection check before any migration.
6. Review the migration list and the backup. Only then run `php artisan migrate --force` against the intended database.
7. Run `php artisan storage:link` if supported by the hosting layout.
8. Configure the website document root to Laravel's `public/` directory. If the hosting plan does not permit that, stop and prepare a verified public-entry-point arrangement rather than exposing the application root or `.env`.
9. Run `php artisan admin:create` interactively to create the first administrator. Do not seed demo accounts into production.
10. Clear/rebuild Laravel caches only after the environment values and route configuration have been checked.
11. Verify the homepage, account verification email, product image uploads, cart, variant stock checks, shipping template calculation, admin access, returns, and order notifications.
12. Test Stripe checkout success, cancellation, failed payment, webhook signature verification, and duplicate webhook delivery using test mode.
13. Switch to live payment credentials only after written approval and successful test results.

## Important release gates

- Group-buy checkout and unique participant counting have automated tests, but the live Stripe workflow still needs an end-to-end test, including concurrent buyers and duplicate webhook delivery.
- Chinese, English, Urdu, Arabic, and Japanese storefront/checkout strings are being expanded; full admin-page translation review is still required.
- Actual scannable QR/barcode image generation and production SMTP delivery must be verified before launch.
- Do not accept live payments until payment, shipping, email, stock deduction, refunds/returns, backups, and restore procedures have been tested on the target environment.
