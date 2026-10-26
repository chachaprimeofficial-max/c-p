# ChachaPrime Marketplace

A Laravel + MySQL single-vendor commerce platform for sourcing products from China and selling internationally.

## Current implementation

- Responsive storefront and separate admin workspace.
- 11-section product create/edit workflow for product details, pricing, B2B tiers, categories, attributes, uploads, stock, identifiers, dimensions, shipping, suppliers, tracking, returns and warranty.
- Retail, B2B quantity pricing and active group-buy campaign management.
- Product image/video/document/blueprint storage.
- Customer registration, email OTP verification, login and customer order history.
- Cart, checkout order records, Stripe-hosted card checkout integration, signed Stripe webhook verification, payment reconciliation and stock checks.
- Admin order fulfilment, courier/tracking details, customer directory and return-request review.
- Searchable category and brand selectors.
- English, Simplified Chinese, Urdu, Arabic and Japanese locale options.
- PKR, CNY, USD, EUR, GBP, JPY, AED, SAR and TRY currency options with cached exchange-rate conversion.
- Optional Gemini AI product-copy drafts.
- Admin integration status pages that do not expose secret values.

## Requirements

- PHP version supported by the Laravel version in `composer.json` (use PHP 8.4 on the target Hostinger account if that is the configured runtime).
- Composer.
- MySQL or MariaDB.
- A web server configured to serve Laravel's `public/` directory.
- SMTP credentials for email verification.
- Stripe test/live keys and a webhook signing secret before card checkout can be enabled.
- Optional Gemini API key for AI product copy.

## Local setup

1. Copy `.env.example` to `.env`.
2. Set a unique `APP_KEY`, database credentials, SMTP configuration and `APP_URL`.
3. Install PHP dependencies with `composer install --no-dev --optimize-autoloader` for production, or `composer install` for development.
4. Run `php artisan key:generate` if no application key has been set.
5. Run `php artisan migrate --force` after checking the database connection and backup status.
6. Run `php artisan storage:link` so public product media can be served.
7. Configure the web root to Laravel's `public/` directory, then clear/rebuild config and route caches as appropriate.
8. Run `php artisan test` and complete a full end-to-end test before going live.

## Production launch checklist

- [ ] Rotate any password that has ever been pasted into chat, a ticket or source-control content. Do not reuse database credentials as mailbox passwords.
- [ ] Keep `.env` outside the public web root and out of Git.
- [ ] Use unique, strong database, mailbox, admin and API credentials.
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, and a correct HTTPS `APP_URL`.
- [ ] Create the first admin account securely; do not rely on demo credentials.
- [ ] Verify SMTP delivery for OTP, order and operational email.
- [ ] Configure Stripe test keys and a signed webhook, then complete successful, cancelled and failed-payment tests. Switch to live keys only after approval and verification.
- [ ] Confirm the production database has migrations applied and no demo/test data.
- [ ] Verify storage permissions, backups, HTTPS, error logging, queue/scheduler needs and restore procedures.
- [ ] Test product uploads, currency selection, all locale options, quantity tiers, order history, returns and admin access on the deployed domain.

## Security notes

- Never commit `.env`, passwords, API keys, production database dumps or customer data.
- Never accept card details directly in this application; use the hosted/tokenized payment provider.
- Currency conversion is a display/quote calculation, not a guarantee of the gateway settlement amount.
- Email and payment integrations must be tested with the actual production account before launch.
- Product specifications and AI-generated copy must be reviewed by a human before publication.

## Status

The repository is being built incrementally and automated checks run through GitHub Actions. Passing CI is not the same as production acceptance: database migration, SMTP delivery, Stripe webhook, file uploads, admin bootstrap and the deployed storefront still require environment-specific validation.
