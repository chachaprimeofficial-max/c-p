# Hostinger deployment

## Requirements
- PHP 8.2 or newer with PDO MySQL, mbstring, fileinfo, OpenSSL and GD
- Composer 2, Git and SSH access
- A MySQL/MariaDB database
- Writable Laravel storage and bootstrap/cache directories

## First deployment
1. Rotate any database or mailbox passwords previously pasted into a chat.
2. Set the domain to use the intended PHP 8.2+ runtime in Hostinger.
3. Review `scripts/deploy-hostinger.sh` before running it.
4. Run the script once from SSH. On first run it clones the project and creates `laravel_app/.env.example` as `.env`, then exits before database changes.
5. Edit `~/domains/chachaprime.com/laravel_app/.env` and enter the database, SMTP and admin bootstrap credentials. Never commit this file.
6. Run the script again. It installs dependencies, migrates tables, seeds the admin only if ADMIN_EMAIL and a 12+ character ADMIN_PASSWORD are configured, and copies the public web files into public_html.

## Admin bootstrap
Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in the server-only `.env` before running `php artisan db:seed --force`. The password is hashed by Laravel. Do not put credentials in GitHub.

## Notes
- The deployment script runs migrations and copies files into public_html. Review the target directory before using it; do not use it if public_html contains files that must be preserved.
- Payment gateway credentials, Gemini API key, exchange-rate availability and SMTP delivery must be configured and verified before production use.
- A green CI run is not a substitute for live-hosting smoke tests, security review or payment-provider approval.
