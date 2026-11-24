# Installation — HelpDesk

These are the exact commands used to create and run this project on Windows. No
global packages are installed and no system settings are changed.

## Environment

| Component | Version / path |
| --- | --- |
| PHP | `php` — PHP 8.4.28 (NTS, VC22 x64) |
| Composer | `composer` |
| Node.js | v24.12.0 |
| npm | 11.6.2 |
| Git | 2.51.1.windows.1 |
| Database | SQLite (`database/database.sqlite`) |
| Dev port | 8105 |

No PATH, registry, `php.ini`, global npm or global Composer configuration was
modified. No Docker or WSL is required.

## 1. Create the project

From the parent folder:

```powershell
composer create-project laravel/laravel helpdesk
cd helpdesk
```

Laravel installed framework **13.43.0**.

## 2. Environment file

```powershell
copy .env.example .env
php artisan key:generate
```

Confirm these values in `.env` for the demo:

```dotenv
APP_NAME="HelpDesk"
APP_URL=http://localhost:8105
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
DB_CONNECTION=sqlite
MAIL_MAILER=log
MAIL_FROM_ADDRESS="support@helpdesk.test"
HELPDESK_TIMEZONE=Africa/Cairo
DEMO_MODE=true
```

For a local debugging session you may set `APP_ENV=local` and `APP_DEBUG=true` in
your own `.env`. The committed `.env.example` deliberately uses
`APP_DEBUG=false`. Never commit `.env`; it is ignored by Git.

## 3. PHP dependencies

```powershell
composer install
composer dump-autoload
```

The project adds no third-party PHP package beyond the Laravel skeleton and its
testing tools. The `.eml` parser is original code in `app/Support/EmlParser.php`.

## 4. Node dependencies and assets

```powershell
npm install
npm run build
```

Production assets are generated in `public/build/` (ignored by Git; rebuild after
cloning).

Fonts are self-hosted and bundled from npm (`@fontsource/almarai`,
`@fontsource/noto-kufi-arabic`). Their OFL licence texts are kept in
`public/fonts/OFL-Almarai.txt` and `public/fonts/OFL-NotoKufiArabic.txt`. No CDN
is used at runtime.

## 5. Database

Create the SQLite file if it does not exist, then migrate and seed the clearly
labelled demo data:

```powershell
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate:fresh --seed
```

The seeder creates:

- 6 team accounts (one inactive), including three `is_demo` accounts for instant
  demo sign-in;
- 5 ticket categories, 4 SLA policies and business hours (Sun–Thu, 09:00–17:00,
  Africa/Cairo);
- 5 tags, 4 bilingual canned replies;
- 3 knowledge-base categories, 8 published articles (Arabic + English) and 1
  draft;
- 12 sample tickets with replies, internal notes, SLA states and CSAT ratings.

All of it is demo data and is labelled in the UI.

## 6. Run

```powershell
php artisan serve --host=127.0.0.1 --port=8105
```

Open <http://localhost:8105>.

## 7. Demo mode

- While `DEMO_MODE=true`, the login page offers one-click **Try the demo** entry
  as Admin, Supervisor or Agent.
- Reset the demo dataset with:

  ```powershell
  php artisan demo:reset --force
  ```

- The scheduler runs `demo:reset` hourly while demo mode is on. Start it with:

  ```powershell
  php artisan schedule:work
  ```

- Set `DEMO_MODE=false` to hide the entry points (`/demo/login` returns 404).

## 8. Scheduled commands (in development)

```powershell
php artisan schedule:work
```

The schedule runs:

- `helpdesk:flag-sla-breaches` every five minutes;
- `helpdesk:import-inbox` every five minutes;
- `demo:reset --force` hourly (no-op when `DEMO_MODE=false`).

## 9. Email-in simulation

```powershell
php artisan helpdesk:import-inbox --dry-run
php artisan helpdesk:import-inbox
```

Fixtures are in `storage/app/inbox/`. Attachments are stored privately under
`storage/app/private/tickets/` and served only through signed temporary URLs.

## 10. Tests and checks

```powershell
php artisan test
npm run build
php vendor/bin/pint --test
```

See [TESTING.md](TESTING.md) for the recorded results.

## Troubleshooting

- **`Vite manifest not found`** — run `npm run build`.
- **Arabic text appears as boxes** — the fonts are bundled into
  `public/build/assets/`; ensure `npm run build` completed and the app serves the
  build directory.
- **Signed links return 403** — the URL expired or `APP_KEY` changed. Use the
  “Track a ticket” form to generate a fresh link.
- **`APP_KEY` missing** — run `php artisan key:generate`.
- **Port 8105 busy** — stop the process using it; do not switch to another
  project's port for delivery.
