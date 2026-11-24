# Security — HelpDesk

> **This is a demonstration build, not a production-ready service.** Demo accounts
> use a published password, mail is written to the application log, and no
> external security review has been performed. See
> [docs/SECURITY-REVIEW.md](docs/SECURITY-REVIEW.md) for the threat model and the
> release 1.1 findings.

## Implemented controls

### Authentication and sessions
- Agent-only sign-in; no public registration.
- Passwords are hashed with Laravel's `hashed` cast (bcrypt).
- Password policy: at least 10 characters including letters and numbers.
- Login attempts are throttled per email + IP (5 per minute) with a lockout message.
- Demo sign-in is separately throttled (10 attempts/minute per IP).
- `session()->regenerate()` on success, session invalidation and CSRF token
  rotation on logout.
- Inactive accounts cannot sign in; already-signed-in inactive users are logged
  out by `EnsureAccountIsActive`.
- `SESSION_SECURE_COOKIE` is configurable for HTTPS deployments.

### Authorization
- Three roles: Administrator, Supervisor, Agent.
- Gates in `AppServiceProvider` and `TicketPolicy` enforce access on every ticket
  action.
- Agents only see unassigned tickets and tickets assigned to themselves.
- Only supervisors/admins can assign a ticket to another agent; agents can claim
  unassigned tickets.
- Administration routes are protected by `can:` middleware; user management,
  SLA/settings and the audit log are admin-only.
- Ticket deletion is administrator-only and removes stored attachment files.

### CSRF and request integrity
- All state-changing forms use POST/PATCH/DELETE with `@csrf`.
- Laravel's `PreventRequestForgery` middleware applies to the whole web group.
- The public submission form includes a hidden honeypot field.
- Public submit, tracking lookup, replies and CSAT submissions are rate limited.

### Signed links
- Public ticket access uses Laravel signed URLs (`/t/{reference}?signature=…`),
  temporary for 90 days by default.
- The tracking lookup requires the reference **and** the matching requester email.
- CSAT uses a separate 30-day temporary signed route and only works for
  solved/closed tickets.
- Attachment downloads use temporary signed storage URLs (30 minutes) generated
  after authorization.

### Uploads
- Allowed extensions only: jpg, jpeg, png, webp, gif, pdf, txt, csv, doc, docx,
  xls, xlsx, zip. SVG is not allowed.
- Maximum 5 MB per file and 5 files per message (configurable).
- Demo users are capped at 2 files / 2 MB per message.
- Laravel `mimes`/`max` validation plus a second extension/size check in
  `AttachmentService`.
- Stored under `storage/app/private/tickets/{ticket_id}/` with UUID filenames;
  original names are sanitised.
- The private disk requires a valid signature. No uploaded file is executed or
  rendered inline.

### Output and data
- Blade auto-escapes output. Ticket messages are rendered with
  `whitespace-pre-line`; no customer HTML is rendered.
- Knowledge-base bodies are displayed as escaped plain text with line breaks.
- Email content is parsed to text; HTML is only used as a fallback and stripped.
- CSV export prefixes spreadsheet formula characters (`=`, `+`, `-`, `@`, tab, CR)
  to limit CSV injection.
- Security headers: `Content-Security-Policy`, `Strict-Transport-Security` (HTTPS
  only), `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
  `Permissions-Policy`, `Cross-Origin-Opener-Policy`,
  `X-Permitted-Cross-Domain-Policies`. See `App\Http\Middleware\SecurityHeaders`.

### Audit trail
- Sensitive admin actions (team member create/update/delete, SLA and business
  hours changes, knowledge-base publish/unpublish/delete) are written to
  `audit_logs` and shown on the admin-only audit page.

### Demo mode
- Controlled by `DEMO_MODE`. When `false`, `/demo/login` returns 404 and the demo
  buttons disappear.
- Demo accounts are flagged `is_demo`: they cannot change email, password, role or
  status, cannot be deleted, and cannot create another administrator.
- A persistent banner states the data is sample data and resets automatically.
- `demo:reset --force` reseeds the demo dataset and is scheduled hourly while demo
  mode is on.

### Email-in safety
- Sender must match the ticket requester before a reply is threaded.
- Repeated `Message-ID` values are ignored.
- Attachment extensions and sizes are validated; invalid attachments are skipped
  with a recorded reason.
- No inbound message is executed; no IMAP credentials exist.

### Privacy
- No trackers, analytics, CDNs or third-party logos.
- Fonts are self-hosted and bundled at build time.
- Mail is written to `storage/logs/laravel.log` (log driver).

## Known limitations

- Demo credentials are one click away while `DEMO_MODE=true`.
- No password reset, email verification, 2FA or account lockout beyond rate
  limiting.
- No antivirus/content-disarm scanning of attachments.
- `script-src 'self'` only: the UI uses Alpine's CSP build (`@alpinejs/csp`),
  which parses expressions without `eval`. `unsafe-inline` is allowed only for
  styles, never for scripts.
- SQLite is not suitable for high concurrency; no row-level locking stress
  testing.
- The audit log records admin actions but not every read.
- Rate limiting uses the configured cache store; behind a reverse proxy, trusted
  proxy configuration must be reviewed.
- Ticket references are sequential and therefore guessable, but signed links and
  email matching are still required.
- The log mail driver means confirmation and CSAT emails are not delivered to real
  inboxes.

## Production checklist (not done here)

1. Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and HTTPS with
   `SESSION_SECURE_COOKIE=true`.
2. Set `DEMO_MODE=false`, do **not** run `DemoDataSeeder`, and remove demo
   accounts.
3. Configure a real transactional mail provider and SPF/DKIM/DMARC.
4. Move to MySQL/PostgreSQL if concurrency exceeds a small team, run backups and
   test restores.
5. Review the CSP, keep HSTS enabled, and add a reverse-proxy/WAF layer.
6. Add antivirus scanning and a quarantine bucket for attachments.
7. Add password reset, MFA and session management.
8. Configure `schedule:run` under a supervisor/cron and monitor failures.
9. Define retention, deletion and export policies; review them with legal.
10. Perform an independent penetration test and accessibility audit.

## Reporting a vulnerability

Use Fedaa's internal security channel. Do not open a public issue containing
exploit details, customer data or credentials.
