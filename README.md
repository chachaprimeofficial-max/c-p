# ChachaPrime Marketplace

Premium single-vendor commerce platform built with Laravel and MySQL/MariaDB.

## Product goals
- Premium responsive storefront and administration dashboard
- Product catalog with media, variations, SKU, barcode/QR identifiers
- Retail, quantity-tier B2B pricing, and group-buy campaigns
- Searchable category/brand selectors
- Localized storefront: English, Simplified Chinese, Urdu, Arabic, Japanese
- Display currencies: PKR, CNY, USD, EUR, GBP, JPY, AED, SAR, TRY
- SMTP email verification and notifications
- Optional Gemini AI integration via server-side API key
- Shipping, tracking, returns, warranty, and order management

## Security
- Never commit `.env`, credentials, API keys, or production database dumps.
- Use a supported Laravel/PHP version that matches the deployment host.
- Payment processing must use a compliant hosted/tokenized payment provider.
- Currency conversion rates are display/quote rates; gateway settlement rules apply.

## Build status
This repository was empty at initial review. The project is being scaffolded in stages; production readiness requires dependency installation, migrations, automated tests, and deployment validation.
