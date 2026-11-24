# Roadmap — HelpDesk

This roadmap is intentionally honest. Items marked **partial** exist but are narrower than a production product would need.

## Implemented

- Public portal: submit, validated attachments, signed tracking, replies, reopen on reply.
- Reference + email lookup that issues a fresh signed link.
- Bilingual Arabic/English UI with RTL/LTR, self-hosted font and a language switch.
- Agent workspace: queues, filters, search, assignment, priorities, statuses, internal notes, canned replies, tags.
- SLA policies per priority, business-hour calculation, breach indicators, scheduled flagging command.
- CSAT via signed link with optional comment.
- Knowledge base with categories, bilingual drafts/published articles and public search.
- Roles and server-side authorization for Admin, Supervisor and Agent.
- Reports: volume trend, response/resolution averages, SLA compliance, CSAT distribution, per-agent table, CSV export.
- Email-in simulation with MIME parsing, reference/`In-Reply-To` threading, sender checks and deduplication.
- Demo data seeder and demo labelling (the demo instance is the fictional **FiberNet ISP** support centre, with Arabic ISP tickets and KB articles).
- Public about, privacy and terms pages; footer owner credit.
- release 1.1 identity: Almarai + Noto Kufi Arabic fonts, mint/deep-teal/coral palette, original speech-bubble heart logo and a task-driven public portal.
- Instant demo sign-in for Admin / Supervisor / Agent, demo-account protections, tightened demo uploads and an hourly `demo:reset` command.
- Content-Security-Policy, HSTS (HTTPS), COOP and hardened auth headers.
- Admin-only audit log for sensitive team, SLA, business-hours and knowledge-base actions.
- 87 automated tests and a production frontend build.

## Missing or partial

| Area | Status | Notes |
| --- | --- | --- |
| Real inbound email (IMAP/API) | Missing | Only `.eml` fixtures in `storage/app/inbox` are supported. No IMAP credentials, polling or provider webhooks. |
| Outbound email delivery | Partial | Uses Laravel's `log` driver by design. No SMTP/provider setup, bounce handling or delivery tracking. |
| Password reset / email verification / MFA | Missing | Accounts are created by an administrator. |
| Attachment preview / virus scanning | Missing | Files are validated by extension/size and served privately, but not scanned or previewed. |
| Rich text or Markdown in KB | Partial | Article bodies are safe plain text with paragraphs; no editor or HTML. |
| Full-text search | Partial | `LIKE` search on tickets/articles. No indexed full-text engine, stemming or typo tolerance. |
| SLA pause/resume | Missing | The clock does not pause for “pending customer” or on-hold states. |
| Per-team business hours/timezones | Partial | One global business-hours calendar and one support timezone. |
| Role management UI | Partial | Fixed three roles; permissions are code-defined, not editable. |
| Audit log UI | Implemented | Admin-only page records team, SLA, business-hours and KB changes. Ticket status/priority/assignment changes still appear only as internal system messages. |
| Bulk actions / merge / split tickets | Missing | Actions are per ticket. |
| Real-time notifications | Missing | No websockets, push or agent notification emails. |
| CSAT reminder/recovery emails | Missing | One link is generated at solve time; no reminder schedule or low-score follow-up workflow. |
| Reports | Partial | Based on tickets created in the selected range; no scheduled email reports, PDF export or custom metric builder. |
| Knowledge-base analytics | Partial | View counter only; no search-term or helpfulness analytics. |
| User restore / transfer | Partial | Users are soft-deleted, but there is no restore UI or email reuse flow. |
| API / webhooks | Missing | Server-rendered Blade app only; no public API tokens or webhooks. |
| Accessibility audit | Partial | Semantic markup, labels, focus rings and contrast were built in; no formal WCAG audit or screen-reader test. |
| Browser automation/visual tests | Missing | HTTP feature tests and a manual smoke pass; no Playwright/Dusk suite. |
| Load/concurrency testing | Missing | SQLite is single-writer; no queue workers under load. |
| Deployment automation | Missing | No Docker image, CI pipeline or infrastructure-as-code; installation is documented manually. |
| Screenshots | Partial | See `docs/screenshots/` and the README; capture more if needed. No fake images. |
| Localization beyond ar/en | Missing | Two locales by design. |
| Data retention/export/GDPR tooling | Missing | Manual database operations only. |

## Suggested next milestones

1. **Production readiness:** SMTP provider, password reset/MFA, CSP/HSTS, antivirus scanning, MySQL/PostgreSQL, monitoring.
2. **Inbox integration:** IMAP or provider webhook ingestion with OAuth, spam handling and bounce processing.
3. **Agent efficiency:** bulk actions, saved views, real-time notifications, SLA pause, merge/split.
4. **Insight:** indexed search, scheduled CSV/PDF reports, KB helpfulness analytics.
5. **Assurance:** Playwright end-to-end suite, accessibility audit, load testing, external penetration test.
