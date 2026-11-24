# Architecture — HelpDesk

## Overview

HelpDesk is a server-rendered Laravel application with a small amount of Alpine.js for progressive interaction. There is no SPA, no API layer and no external service dependency.

```
Browser (RTL/LTR Blade UI)
        |
        v
Laravel HTTP kernel
  - SetLocale, SecurityHeaders
  - session, CSRF, auth, active-account check
        |
        +--> Public portal controllers ---- signed URL middleware
        +--> Agent controllers ------------ Gates + TicketPolicy
        +--> Admin controllers ------------ can: middleware
        |
        v
Services: TicketService, SlaService, SlaCalculator,
          AttachmentService, EmailInboxImporter, EmlParser,
          ReportService
        |
        v
Eloquent models --> SQLite (database/database.sqlite)
        |
        +--> Private files: storage/app/private/tickets
        +--> Log mail: storage/logs/laravel.log
        +--> Inbox fixtures: storage/app/inbox
```

## Directory map

| Path | Responsibility |
| --- | --- |
| `app/Enums` | Typed roles, statuses, priorities, channels, authors, KB states; labels/colours. |
| `app/Models` | Eloquent entities, scopes, casts and relationships. |
| `app/Services` | Business logic: ticket lifecycle, SLA, attachments, email import, reports. |
| `app/Support` | Original MIME parser, `Audit` recorder and date/duration helpers. |
| `app/Http/Controllers/PublicPortal` | Public submit, track, signed conversation, CSAT, KB. |
| `app/Http/Controllers/App` | Dashboard, tickets, canned replies, taxonomy, SLA, users, KB admin, reports, audit log. |
| `app/Http/Requests` | Server-side validation, including authorization and localized attribute names. |
| `app/Policies` | Ticket visibility and mutation rules. |
| `app/Mail` | Three transactional Mailables (created, reply, solved/CSAT). |
| `app/Console/Commands` | `helpdesk:import-inbox`, `helpdesk:flag-sla-breaches`, `demo:reset`. |
| `resources/views` | Blade layouts, components, public pages, workspace, mail, errors. |
| `lang/{ar,en}` | Every UI string, enum label, validation message and mail string. |
| `database/migrations` | Schema, one concern per migration group. |
| `database/seeders/DemoDataSeeder.php` | Clearly labelled demo dataset. |
| `tests` | 82 feature/unit tests. |
| `storage/app/inbox` | `.eml` demo fixtures for the inbox simulation. |

## Data model

```
User (role: admin|supervisor|agent, is_active, is_demo, locale, soft deletes)
  |-- assignedTickets --> Ticket.assigned_to
  |-- createdTickets  --> Ticket.created_by

TicketCategory 1---* Ticket
Ticket 1---* TicketMessage 1---* TicketAttachment
Ticket *---* Tag (tag_ticket)
Ticket 1---1 CsatRating
Ticket *---* User (assignee/creator)

SlaPolicy (one per priority)      BusinessHour (seven rows)
CsatRating
CannedReply
KbCategory 1---* KbArticle (row per locale, linked by translation_key)
InboxMessage (message_id unique, import status and ticket link)
AuditLog (user_id, action, subject, metadata, ip, created_at)
```

Key ticket fields: `reference` (`TKT-YYYY-NNNN`, route key), subject, description, status, priority, channel, requester name/email/locale, assignee, first-response/resolution due/actual/breached timestamps, last reply time.

Message fields: author type, author snapshot (name/email), body, internal flag, email `message_id`/`in_reply_to`, source.

## Core flows

### Public submission
1. `POST /submit` validates name, email, subject, active category, message, attachments and honeypot; rate limited.
2. `TicketService::create` opens a transaction, generates the next yearly reference, stores the ticket and initial requester message, applies SLA targets and stores attachments privately.
3. A signed 90-day tracking URL is generated and emailed through the log mailer.
4. The visitor is redirected to the signed conversation.

### Signed conversation and tracking
- `GET /t/{ticket}?signature=…` uses Laravel's `signed` middleware. Tampering or removing the signature returns 403.
- Only public messages are loaded; internal notes and system audit entries never reach the public view.
- `POST` to the same signed URL adds a requester message, reopens solved/closed tickets and records `last_reply_at`; CSRF and rate limits still apply.
- If a link is lost, `POST /track` requires reference + email and redirects to a fresh signed URL. Mismatches return a generic validation error.

### Agent workspace
- `Ticket::scopeVisibleTo` limits agents to unassigned and self-assigned tickets; supervisors/admins see all.
- Every action calls `authorize()` against `TicketPolicy`; assignment rules are re-checked in the controller.
- An agent replying to an unassigned ticket claims it. Internal notes are stored as messages with `is_internal = true` and no mail is sent.
- Status/priority/assignment changes are recorded as internal system messages for an in-ticket audit trail.
- Solving records resolution time (and breach if late) and sends the CSAT link.

### SLA calculation
- `SlaPolicy` stores first-response and resolution minutes per priority and a `use_business_hours` flag.
- `BusinessHour` stores seven weekday windows. Times are wall-clock times in `config('helpdesk.timezone')` (default `Asia/Riyadh`).
- `SlaCalculator::dueAt` converts the start to the support timezone, walks forward through enabled windows, skips closed days/evenings, and returns a UTC timestamp.
- `helpdesk:flag-sla-breaches` (every five minutes) persists breach timestamps for unresolved overdue tickets. Responses/solves also record a breach immediately when late.
- If no active policy exists, documented fallback targets are used.

### CSAT
- `TicketSolvedMail` contains a 30-day temporary signed CSAT URL.
- `CsatController` allows one rating for solved/closed tickets, rejects out-of-range scores, and never overwrites an existing rating.

### Email-in simulation
1. `helpdesk:import-inbox` reads `.eml` files (all or one).
2. `EmlParser` parses headers (including RFC 2047 encoded words), multipart boundaries, base64/quoted-printable bodies, charsets and attachments.
3. Threading order: `TKT-YYYY-NNNN` in the subject, then `In-Reply-To`/`References`.
4. A different sender is rejected and recorded; duplicates by `Message-ID` are skipped.
5. Missing tickets are created with channel `email`, locale detection (Arabic script or English) and the first active category.
6. Attachments are size/type checked; rejected ones are skipped and noted on the import record.

### Reports
- `ReportService::build` selects tickets created in the local-time reporting range, computes averages/compliance from real timestamps, CSAT from submissions in range, a per-agent table and a daily trend.
- Compliance percentages use only measurable tickets (responded / resolved) and expose their sample sizes.
- CSV export includes the summary and per-agent table with a UTF-8 BOM; formula-leading cells are neutralised.

### Knowledge base
- One row per locale, linked by a `translation_key`; admins can pair translations.
- Public queries require `status=published`, `published_at <= now`, and the active locale.
- Drafts are excluded from index, category and search queries; direct access returns 404.
- Bodies are safe plain text rendered with line breaks.

### Audit log
- `App\Support\Audit::record()` writes `audit_logs` rows (actor, action, subject, metadata, IP) for team create/update/delete, SLA and business-hours changes, and KB publish/unpublish/delete.
- `/app/audit` is protected by the `view-audit` gate (administrators only).

### Demo mode
- `DEMO_MODE` gates the `/demo/login` route and the demo buttons on the login and public pages.
- The demo tenant is the fictional **FiberNet ISP** support centre; the seeder creates Arabic ISP tickets, KB articles and categories, with a small English-speaking minority.
- Demo users are flagged `is_demo`: `UserController` blocks identity/role/status changes and deletion, and blocks a demo actor from creating another administrator. `AttachmentService` caps demo uploads.
- `demo:reset --force` runs `migrate:fresh --seed` and is scheduled hourly while demo mode is on.

## Security boundaries

| Boundary | Control |
| --- | --- |
| Public → ticket | signed URL; no sequential-ID access |
| Public lookup | reference + matching email; throttled |
| Attachment access | 30-minute signed storage URL; private disk |
| Agent → workspace | session auth, active-account middleware, gates/policies |
| Admin → settings | role gates and `can:` route middleware |
| Forms | CSRF, server-side validation, escaped output |
| Uploads | extension/MIME/size/count limits, UUID paths, private storage |
| Headers | CSP, HSTS (HTTPS), COOP and baseline security headers |
| Admin actions | audit log for sensitive team/settings/KB changes |

## Localization

- `SetLocale` middleware validates `ar|en`, sets Laravel and Carbon locales, and shares direction with views.
- All strings live in `lang/ar` and `lang/en`; tests scan literal translation keys and assert the two key sets are identical.
- Layout uses CSS logical properties (`start/end`, `ms/me`, `ps/pe`) so RTL is structural rather than mirrored by hand.
- Almarai (UI/body) and Noto Kufi Arabic (headings) are self-hosted via `@fontsource/*` and bundled by Vite, with Arabic + Latin subsets and a system fallback.

## Testing strategy

- Feature tests cover HTTP behaviour, authorization, signed links, lifecycle, import, KB visibility, reports, demo mode, security headers and the audit log.
- Unit tests cover the business-hours SLA calculator with fixed dates.
- `RefreshDatabase` with SQLite in-memory; `Mail::fake()`; `withoutVite()` so tests do not depend on built assets.
- Manual smoke test covers every page and both locales on port 8105, plus real screenshots via headless Chrome.

## Known architectural limits

- SQLite and synchronous mail/report work; no queue workers.
- One global business-hours calendar; SLA does not pause.
- `LIKE` search rather than an indexed search engine.
- No API, webhooks or real-time transport.
