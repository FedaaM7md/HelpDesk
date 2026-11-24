# Security Review — HelpDesk

Date: 2026-11-24 · Scope: the HelpDesk Laravel application in this folder.

## Threat model

Assets: customer ticket contents and attachments, customer email addresses,
staff accounts, SLA/settings configuration, the audit trail.

Likely adversaries and abuse cases:

1. Anonymous visitor forging or enumerating ticket links to read other people's
   conversations (IDOR / URL guessing).
2. Credential stuffing or brute-force against the agent sign-in.
3. A low-privilege agent reaching administration pages or another agent's tickets.
4. Malicious file uploads (executables, SVG with script, oversized files).
5. Injected HTML/script in ticket bodies or knowledge-base content (stored XSS).
6. Formula-injection in CSV report exports opened in Excel.
7. Demo-account abuse: changing the demo identity, deleting it, or escaping the
   demo sandbox.

## What was reviewed and confirmed

- **Authorization on every workspace action**: controllers call
  `authorize()`/policies and the routes use `can:` middleware. Feature tests cover
  agent/supervisor/admin separation and cross-agent ticket access (IDOR).
- **Public ticket access** uses Laravel signed URLs; the reference + email lookup
  only issues a fresh signed link and never reveals whether a ticket exists.
- **Authentication**: login is throttled per email+IP (5/min) plus a controller
  rate-limiter; sessions are regenerated on sign-in; inactive accounts are
  rejected and signed out by `EnsureAccountIsActive`.
- **Password policy**: team passwords require at least 10 characters with letters
  and numbers (server-side `Password` rule).
- **Output escaping**: Blade auto-escapes; no `{!! !!}` renders user data (the one
  `nl2br(e(...))` in the reply mail escapes first). Knowledge-base bodies render
  as escaped plain text.
- **Uploads**: extension allow-list, size limit, random UUID filenames, stored on
  the private `local` disk and served via temporary signed URLs. SVG is not in
  the allow-list.
- **CSV export**: cells are guarded against formula injection before output.
- **Security headers**: `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`, a
  `Content-Security-Policy` and (over HTTPS) HSTS. See `SecurityHeaders`.
- **Dependencies**: `composer audit` and `npm audit` report no advisories.

## Findings fixed in release 1.1

1. **Inline event handlers blocked under CSP.** Removed every `onsubmit`/`onclick`
   attribute and moved confirmations to Alpine directives, so the CSP can forbid
   inline scripts.
2. **No CSP previously.** Added a policy with `script-src 'self'`
   (the UI uses Alpine's CSP build, `@alpinejs/csp`, so no `eval` is needed) and
   `style-src 'self' 'unsafe-inline'`; inline `<script>` and inline handlers stay
   blocked. `frame-ancestors 'self'`, `object-src 'none'`, `base-uri 'self'`.
3. **HSTS missing.** Added `Strict-Transport-Security` for HTTPS requests only.
4. **COOP missing.** Added `Cross-Origin-Opener-Policy: same-origin`.
5. **Weak password policy.** Added the letters+numbers requirement.
6. **No audit trail.** Added an `audit_logs` table and an admin-only audit page,
   recording user-management, SLA, business-hours and knowledge-base changes.
7. **Demo accounts were editable/removable.** Demo users are now flagged
   `is_demo`, cannot change email/password/role/status, cannot be deleted, and a
   demo user cannot create another administrator. Demo uploads are capped at 2
   files / 2 MB.

## Remaining risks (documented, not hidden)

- **`script-src 'self'`** only: the UI uses Alpine's CSP build (`@alpinejs/csp`),
  which parses expressions without `eval`, so no `unsafe-eval` is needed.
- **`style-src 'unsafe-inline'`** is kept for dynamic progress-bar widths and
  Tailwind utility styles. Output is escaped, so CSS injection risk is low.
- **No MFA, email verification, password reset or malware scanning.** These are
  production gaps listed in `ROADMAP.md`.
- **Throttling is per-instance** (cache/database store); behind multiple servers
  it must use a shared cache.
- **SQLite** is single-writer; concurrency/load was not tested.
- **Demo reset** uses `migrate:fresh --seed`, which wipes the whole database. It
  only runs when `DEMO_MODE=true` and is scheduled hourly for the demo.
- **Mail** uses the `log` driver only; nothing leaves the machine.

## Demo hardening summary

- `DEMO_MODE=false` hides all demo entry points and returns 404 from
  `/demo/login`.
- Demo sign-in is rate-limited to 10 attempts/minute per IP.
- A persistent banner states that data is sample data and resets automatically.
