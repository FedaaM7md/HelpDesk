# Testing — HelpDesk

**Test date:** 24 November 2026
**Environment:** Windows, PHP 8.4.28, Laravel 13.43.0, Node 24.12.0, npm 11.6.2,
SQLite in-memory for tests, SQLite on disk for the smoke test.

## Commands and results

### 1. PHP test suite

```powershell
php artisan test
```

Final result (actual output of Laravel 13's test runner):

```
{"tool":"phpunit","result":"passed","tests":87,"passed":87,"assertions":363,"duration_ms":5754}
```

No tests were skipped, deleted or weakened. The suite uses `RefreshDatabase` against
SQLite `:memory:`, `Mail::fake()` and `withoutVite()`, so it does not depend on
built assets or external services.

Coverage by area:

| Area | Tests |
| --- | --- |
| Ticket lifecycle, public submission, reopen, internal-note privacy | `PublicTicketTest` |
| Email MIME parsing, threading by reference and `In-Reply-To`, duplicates, sender mismatch, attachment rejection, dry run | `EmailInboxTest` |
| Attachment type/size/count validation, honeypot, private signed download | `AttachmentTest` |
| Signed ticket links, tampering, expiry, CSAT submission and limits | `SignedLinkTest` |
| SLA calculator with business hours, policy application, breach command, late responses | `SlaCalculatorTest`, `SlaTest` |
| RBAC, ticket visibility, assignment restrictions, inactive accounts, admin deletion | `AuthorizationTest` |
| Knowledge-base locale visibility, drafts, search leakage, publishing, permissions | `KnowledgeBaseTest` |
| Report math, per-agent table, CSV export, date-range guard | `ReportTest` |
| Arabic/English direction, language switch, localized validation | `LocaleTest` |
| Login, throttling, inactive accounts, remember-me, logout | `AuthTest` |
| Agent workspace replies, notes, status/tags, canned replies, filters, search | `AgentWorkspaceTest` |
| Literal translation-key coverage **and** ar/en key-set parity | `TranslationCoverageTest` |
| Instant demo sign-in, 404 when disabled, demo identity locks, demo reset no-op | `DemoModeTest` |
| CSP, HSTS absence over HTTP, baseline headers, and **no `unsafe-eval`/dev hosts** | `SecurityHeadersTest` |
| Audit trail on user creation, admin-only audit page, listing | `AuditLogTest` |
| About / privacy / terms pages and footer owner credit | `PublicPagesTest` |
| Demo seeder realism: ≥24 tickets, ≥85% Arabic, ISP categories, KB, ≥40 solved/CSAT, ≤15% active breached | `DemoSeederTest` |

### 2. Frontend build

```powershell
npm run build
```

Final result (abridged):

```
vite v8.3.8 building client environment for production...
✓ 10 modules transformed.
public/build/assets/app-*.css   90.73 kB │ gzip: 14.07 kB
public/build/assets/app-*.css    2.97 kB │ gzip:  0.48 kB  (font faces)
public/build/assets/app-*.js    72.09 kB │ gzip: 23.70 kB  (Alpine CSP build)
public/build/assets/*.woff2     (Almarai + Noto Kufi Arabic, Arabic + Latin subsets)
✓ built in 528ms
```

The fonts are bundled from `@fontsource/*`; there is no runtime CDN request. The
UI uses Alpine's CSP build (`@alpinejs/csp`), so `script-src` can stay at
`'self'` with no `unsafe-eval`.

### 3. Formatting and dependency audits

```powershell
php vendor/bin/pint --test
composer audit
npm audit
```

- Pint: `{"tool":"pint","result":"passed"}`
- Composer: `No security vulnerability advisories found.`
- npm: `found 0 vulnerabilities`

### 4. Manual smoke test (real server)

Served with:

```powershell
php artisan serve --host=127.0.0.1 --port=8105
```

Database reseeded with `artisan migrate:fresh --seed`, then signed in through the
real login form as `admin@demo.helpdesk.test` (form POST with CSRF, same as a
browser). Every page below returned **HTTP 200** with no Blade/Vite/missing-variable
error:

- Public: `/`, `/submit`, `/track`, `/kb`, `/login`
- Workspace: `/app`, `/app/tickets`, `/app/tickets/create`,
  `/app/tickets/TKT-2026-0001`
- `/app/canned-replies`, `/app/tags`, `/app/categories`
- `/app/sla`, `/app/settings/business-hours`
- `/app/users`, `/app/users/create`
- `/app/kb`, `/app/reports`, `/app/audit`

Demo sign-in (`POST /demo/login` with `role=admin`) returned 200 and issued an
authenticated session. Security headers were confirmed on a real response:
`Content-Security-Policy`, `X-Frame-Options: SAMEORIGIN`,
`X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`,
`Cross-Origin-Opener-Policy: same-origin`; HSTS is correctly absent over plain HTTP.

The development server was stopped after the smoke test; port 8105 is free.

### 5. Screenshots and layout audit

Real captures (Arabic + English, desktop + 375px phone, public + workspace) are in
[docs/screenshots/](docs/screenshots/). They were produced with
`scripts/capture-screenshots.mjs` (headless Chrome over CDP, including a real demo
sign-in for the workspace shots). No mock or placeholder product images.

The same script audits layout: the workspace sidebar was checked at 375, 768, 1024,
1280 and 1440 in **both** ar and en (hidden off-canvas below `lg`, visible and
correctly placed at `lg`+), every main page reported `scrollWidth == 375` at 375px
(no horizontal scrolling), no button/control under 44px was found at 375px, and the
Alpine CSP build was verified by toggling the mobile menu (0 → 213px).

### 6. What was not tested

- Real SMTP delivery (the product intentionally uses the `log` mail driver).
- IMAP/real mailbox ingestion (not implemented; `.eml` simulation only).
- Concurrent writes under load (SQLite is single-writer).
- Screen-reader testing with physical assistive technology; accessibility was
  reviewed in markup and keyboard focus styles.
- Browser-specific visual regression on Safari/Firefox; the smoke test used HTTP
  responses plus Chrome screenshots.

## Reproducing

```powershell
php artisan test
npm run build
php vendor/bin/pint --test
```

If assets are not built, run `npm run build` before opening the app. Tests call
`withoutVite()` so the PHP suite does not depend on built assets.
