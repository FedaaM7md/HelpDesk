# Decisions — HelpDesk

Short architecture decision records. Each records the context, the decision and the consequence.

## ADR-001 — Laravel 13 on PHP 8.4

**Context:** The task requires the latest stable Laravel compatible with PHP 8.4, Blade, Tailwind, Alpine and SQLite.

**Decision:** Install `laravel/laravel` 13.43.0 with the pinned PHP 8.4 binary. Keep the standard Laravel directory structure and service container.

**Consequence:** Modern framework features and security patches are available; the project is coupled to Laravel 13 conventions (attribute-based model metadata, Vite 8, Tailwind 4). No upgrade path was attempted beyond the installed release.

## ADR-002 — Own RBAC instead of a package

**Context:** The brief requires three roles and "own RBAC"; adding `spatie/laravel-permission` would add a dependency and database tables before the permission model is understood.

**Decision:** A typed `UserRole` enum on `users.role`, gates in `AppServiceProvider`, a `TicketPolicy`, and `can:` middleware on administration routes. Assignment rules that depend on the ticket state live in the controller.

**Consequence:** The permission model is explicit and easy to test. Adding custom roles later requires code changes rather than an admin UI. This is documented in the roadmap.

## ADR-003 — Signed URLs for all public ticket access

**Context:** Customers must follow a ticket without creating an account, while ticket references are sequential and guessable.

**Decision:** Use Laravel signed routes (`signed` middleware) for ticket conversations and temporary signed routes for CSAT. Provide a reference + email lookup that mints a fresh link. Never expose an unsigned ticket route.

**Consequence:** Links are private but can be forwarded; expiry (90 days) requires using the lookup form again. If `APP_KEY` changes, old links break. No ticket token table is needed.

## ADR-004 — Private attachment disk with temporary URLs

**Context:** Support attachments can contain sensitive data. Laravel 13 can serve the private local disk through signed `/storage/...` URLs.

**Decision:** Store attachments under `storage/app/private/tickets/{id}/{uuid}.ext` on the `local` disk. Validate type/size/count on upload. Generate 30-minute temporary URLs only after the ticket page authorization check.

**Consequence:** Files are not web-accessible by path, and links expire. Email attachment handling reuses the same service. No virus scanning is performed; a production build must add it.

## ADR-005 — Original `.eml` parser instead of a parser package

**Context:** Composer's available MIME parser (`zbateson/mail-mime-parser`) requires `guzzlehttp/psr7 ^2.5`, while Laravel 13/Guzzle 8 pins PSR-7 3.x. Downgrading would destabilise the framework. No IMAP extension is installed.

**Decision:** Write a focused, dependency-free MIME parser in `app/Support/EmlParser.php` supporting multipart, encoded headers, base64/quoted-printable, common charsets and attachments. Use it only for the inbox simulation.

**Consequence:** Full control and no licence/dependency risk, but the parser handles the common cases, not every exotic MIME construct. Real inbound mail should use a provider API or a maintained parser in production.

## ADR-006 — Business hours as a global calendar with a per-policy toggle

**Context:** SLA policies need business-hour targets, but per-team calendars would add substantial admin UI.

**Decision:** Seven `business_hours` rows (weekday, opens, closes, enabled) plus `sla_policies.use_business_hours`. The calculator runs in `config('helpdesk.timezone')` and stores UTC.

**Consequence:** One calendar for the whole installation, with a clean path to per-team calendars later. Policies can opt out to 24/7 targets. Validation prevents closing before opening or overnight windows.

## ADR-007 — CSAT through a separate signed temporary route

**Context:** Satisfaction must be collected after solving, ideally without another login.

**Decision:** On solve, generate a 30-day temporary signed URL to `/csat/{reference}`. The page and POST accept one rating per ticket; unsolved tickets return 404; duplicate ratings are ignored.

**Consequence:** Simple and private. A forwarded link could be rated by someone else (score only, no identity); production should consider a single-use token if that matters. No reminder workflow exists.

## ADR-008 — Knowledge base as one row per locale

**Context:** Articles need Arabic and English versions with independent drafts and URLs.

**Decision:** `kb_articles` stores `locale`, `slug` and a shared `translation_key`. Public queries filter by active locale and published state. The admin form lets an author pair translations with the same key.

**Consequence:** Natural URLs and per-locale publishing. There is no automatic fallback to the other locale and no side-by-side translation editor; both are roadmap items.

## ADR-009 — Plain-text ticket and KB content

**Context:** Rendering customer HTML or a rich-text editor introduces XSS and sanitisation complexity.

**Decision:** Store and display plain text. Use `whitespace-pre-line` for messages and `nl2br(e(...))` for article bodies. HTML emails are converted to text for the fallback.

**Consequence:** Strong XSS posture and predictable RTL rendering. Users cannot format rich content or embed images inline; attachments cover that need.

## ADR-010 — Log mail driver only

**Context:** The brief explicitly requires Laravel's `log` mailer and forbids Mailpit or a mail server.

**Decision:** All Mailables use the default `log` transport. Tests use the `array` transport and `Mail::fake()`.

**Consequence:** Emails can be inspected in `storage/logs/laravel.log`, but nothing is delivered externally. The app never claims otherwise. Production must configure SMTP/API delivery.

## ADR-011 — Demo data is seeded, labelled and sandboxed

**Context:** Reviewers need a populated app, but fake records must not masquerade as real customers, and a demo must not become an escalation path.

**Decision:** `DatabaseSeeder` calls `DemoDataSeeder`, which creates realistic but clearly synthetic records for the fictional **FiberNet ISP** support centre (Arabic ISP tickets, KB articles and categories, with a small English-speaking minority). `DEMO_MODE=true` shows a small banner in the workspace and a discreet footer note on public pages, offers one-click demo sign-in per role, and hides everything when set to `false`. Demo accounts carry `is_demo` and cannot change identity or be deleted; demo uploads are capped; `demo:reset` reseeds hourly.

**Consequence:** Immediate evaluation without pretending the data is real, and a contained demo surface. Production must disable demo mode and avoid the seeder.

## ADR-012 — SQLite for portability

**Context:** The stack specifies SQLite and several agents run in isolation on one Windows machine.

**Decision:** Use the default SQLite connection with foreign keys enabled and migrations that avoid database-specific SQL. Date grouping in reports happens in PHP for timezone correctness.

**Consequence:** Zero external services and simple tests. SQLite's single-writer behaviour limits concurrency; production should move to MySQL/PostgreSQL.

## ADR-013 — Server-side filters and Blade, not a SPA

**Context:** The task requires Blade + Alpine + Vite with an Arabic-first, responsive UI.

**Decision:** Render pages server-side; use Alpine only for menus, tabs, copy buttons and file-name previews. Filters are GET forms with query-string pagination.

**Consequence:** Fast first render, simple deployment and accessible forms. Interactions are page reloads; no optimistic UI or real-time updates.

## ADR-014 — UTC storage, support-timezone display

**Context:** SLA and reports must match the team's working day, while storage must remain unambiguous.

**Decision:** Store timestamps in UTC. Display and calculate business hours in `HELPDESK_TIMEZONE` (default `Asia/Riyadh`). Helpers convert dates for the UI. Reports convert grouping dates to the support timezone.

**Consequence:** Consistent storage and correct local-day reporting. Changing the support timezone affects interpretation of existing business-hour windows but not stored instants.

## ADR-015 — Test behaviour, not implementation

**Context:** The mandatory test list spans lifecycle, email, attachments, SLA, signed links, permissions, KB and reports.

**Decision:** Use Laravel HTTP feature tests with real routing, middleware, validation and database, plus a unit test for the SLA calculator. Add a translation-coverage test and a CSV/mail rendering test. Do not mock the services under test.

**Consequence:** 82 tests exercise the actual stack; they are slower than pure unit tests but catch integration defects (as they did during delivery). There is no browser automation or coverage percentage target.

## ADR-016 — Security headers with a strict CSP

**Context:** release 1.1 requires a real Content-Security-Policy. The default Alpine build
compiles `x-*` expressions with `new Function` and thus needs `unsafe-eval`. Inline
event handlers would need `unsafe-inline`, which the brief discourages.

**Decision:** Use Alpine's CSP build (`@alpinejs/csp`), which parses expressions
without `eval`, and register components with `Alpine.data` (`workspaceShell`,
`userMenu`, `publicNav`, `passwordField`, `replyComposer`, `fileList`,
`copyButton`, `confirmSubmit`, `historyBack`). Add `Content-Security-Policy`
(`default-src 'self'`, `script-src 'self'`, `style-src 'self' 'unsafe-inline'`,
`frame-ancestors 'self'`, `object-src 'none'`, `base-uri 'self'`), HSTS over HTTPS
only, and `Cross-Origin-Opener-Policy`. Remove all inline event handlers and bind
confirmations through Alpine directives. The Vite dev origin is allowed only in
local debug **and** only while `public/hot` exists, using `localhost`/`127.0.0.1`.

**Consequence:** `script-src` has no `unsafe-eval` and no `unsafe-inline`; inline
`<script>` and inline handlers are blocked. Only inline styles remain allowed
(dynamic widths). A test asserts the script directive has no `unsafe-eval` and the
header contains no dev hosts.

## ADR-017 — Brand tokens and self-hosted type

**Context:** The product must have a distinct, non-generic identity with Arabic-first typography and no CDN.

**Decision:** Define a token layer in `resources/css/app.css` (deep teal, mint, coral, warm white, ink) and re-point the `brand` scale to deep teal. Use Almarai (UI) and Noto Kufi Arabic (headings), self-hosted via `@fontsource/*` and bundled by Vite. Remap font weights to the available Almarai weights so no faux-bold is synthesised.

**Consequence:** One source of truth for colour and type, a coherent palette across every view, and no runtime font request. The brand guide lives in `docs/BRAND.md`.

## ADR-018 — Lightweight database audit log

**Context:** The app has administrative features, and release 1.1 requires an audit trail for sensitive actions.

**Decision:** Add an `audit_logs` table and `App\Support\Audit::record()`, called from user management, SLA, business-hours and KB controllers. Expose an admin-only `/app/audit` page. Do not use a third-party activity-log package.

**Consequence:** A clear, testable trail without extra dependencies. It records admin mutations, not every read; richer filtering is a roadmap item.

## ADR-019 — Owner credit in the footer only

**Context:** The owner decided that products should look like real standalone products, not portfolio pieces.

**Decision:** Remove "by Fedaa" from logos, headers, login cards and titles. Keep a single quiet footer credit — "Designed & built by Fedaa Mohamed" / "تصميم وتطوير: فداء محمد" — linking to https://fedaamohamed.com with `rel="noopener"`. Legal ownership remains in `LICENSE`, `COPYRIGHT.md`, README and package metadata.

**Consequence:** The brand stands alone in the UI; the owner credit is discoverable but not intrusive. About, privacy and terms pages were added for product realism.
