# HelpDesk — Brand Guide

HelpDesk is a support product. The brand is **HelpDesk**; the product name stands
alone in the interface. The owner credit appears only in the footer bottom line
("Designed & built by Fedaa Mohamed" / "تصميم وتطوير: فداء محمد", linking to
https://fedaamohamed.com).

## Personality

Human and empathetic. The product should feel like a calm, attentive support
desk — not a sterile dashboard and not a marketing page. Concrete language,
short sentences, and apologies only when something actually went wrong.

## Logo

- Two overlapping speech bubbles that lean into a subtle heart shape.
- Left bubble: deep teal `#0F5C55`. Right bubble: coral `#F26B5B`. White
  conversation dots on the teal bubble.
- Wordmark: **HelpDesk** in both languages (the product name always renders in
  Latin). No attribution runs beside the mark.
- Never place the mark inside a rounded square, never add gradients or glow, and
  never recolour the heart bubbles outside the palette below.

The SVG lives in `resources/views/components/logo.blade.php`; the favicon is
`public/favicon.svg`.

## Palette

| Token | Hex | Use |
| --- | --- | --- |
| Deep teal | `#0F5C55` | Primary actions, sidebar, links, focus rings |
| Mint | `#E6F4EF` | Soft surfaces, customer chat bubbles, category tiles |
| Coral | `#F26B5B` | Human accent: logo heart, demo call-to-action, destructive buttons |
| Warm white | `#FFFDF9` | Page background |
| Ink | `#1F2A2A` | Headings and primary text |

Supporting functional colours stay conventional: amber for warnings/due-soon,
coral/rose for danger and breaches, slate for neutral text. Money and reference
numbers use tabular figures; Arabic and English numerals follow the active
locale.

## Typography

- **Body / UI:** Almarai (weights 400, 700, 800).
- **Headings:** Noto Kufi Arabic (weights 400, 500, 700), falling back to Almarai
  for Latin headings.
- Both fonts are self-hosted through `@fontsource/*` and bundled by Vite. No CDN.

## Voice and glossary

| Concept | English | Arabic |
| --- | --- | --- |
| Ticket | ticket | تذكرة |
| Requester | requester / customer | مقدّم الطلب / العميل |
| Agent | agent | موظف الدعم |
| Queue | queue | قائمة الانتظار |
| SLA target | response target | هدف الاستجابة |
| Knowledge base | knowledge base | مركز المعرفة |
| Canned reply | canned reply | رد جاهز |

Rules:

- Arabic: clear Modern Standard Arabic that reads naturally to Egyptians. Avoid
  Gulf-specific or rare vocabulary. Correct hamzas and grammar.
- English: concise product English. No hype ("seamless", "elevate", "unlock").
- Apologise only when the team caused a problem; otherwise stay warm and factual.
- Keep Arabic and English key sets identical (enforced by a test).

## Do / Don't

- **Do** show real product UI (the search box, the ticket thread) with demo data.
- **Do** use asymmetric, task-driven layouts and chat-bubble threads.
- **Do** keep numbers tabular and references in a mono context.
- **Don't** use gradient blobs, glassmorphism, sparkle badges or emoji icons.
- **Don't** build identical three-card feature rows or "trusted by" strips.
- **Don't** put "Fedaa" in page titles, headings or demo business names.
