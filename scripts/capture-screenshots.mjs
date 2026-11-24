// HelpDesk — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.
// Dev utility: capture real screenshots and layout diagnostics with headless
// Chrome via CDP. Usage: start `php artisan serve --port=8105`, then:
//   node scripts/capture-screenshots.mjs docs/screenshots
import { spawn } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const CHROME = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT = process.argv[2] ?? 'docs/screenshots';
const BASE = process.env.SHOT_BASE ?? 'http://127.0.0.1:8105';
const PORT = 9223;
const PROFILE = `${process.env.TEMP}\\hdcdp-${Date.now()}`;
mkdirSync(OUT, { recursive: true });
rmSync(PROFILE, { recursive: true, force: true });

const chrome = spawn(
    CHROME,
    [
        '--headless=new',
        '--disable-gpu',
        '--hide-scrollbars',
        '--no-first-run',
        '--no-default-browser-check',
        `--remote-debugging-port=${PORT}`,
        `--user-data-dir=${PROFILE}`,
        'about:blank',
    ],
    { stdio: 'ignore' },
);

async function waitVersion() {
    for (let i = 0; i < 60; i++) {
        try {
            const r = await fetch(`http://127.0.0.1:${PORT}/json/version`);
            if (r.ok) return;
        } catch {}
        await sleep(200);
    }
    throw new Error('chrome not ready');
}
await waitVersion();

let target;
try {
    const created = await fetch(`http://127.0.0.1:${PORT}/json/new?about:blank`, { method: 'PUT' });
    target = await created.json();
} catch {
    const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
    target = list.find((t) => t.type === 'page') ?? list[0];
}

const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((res, rej) => {
    ws.onopen = res;
    ws.onerror = rej;
});

let id = 0;
const pending = new Map();
const listeners = new Map();

ws.onmessage = (ev) => {
    const msg = JSON.parse(ev.data);
    if (msg.id && pending.has(msg.id)) {
        const { resolve, reject } = pending.get(msg.id);
        pending.delete(msg.id);
        msg.error ? reject(new Error(JSON.stringify(msg.error))) : resolve(msg.result);
    } else if (msg.method) {
        (listeners.get(msg.method) ?? []).slice().forEach((fn) => fn(msg.params));
    }
};

function send(method, params = {}) {
    const mid = ++id;
    return new Promise((resolve, reject) => {
        pending.set(mid, { resolve, reject });
        ws.send(JSON.stringify({ id: mid, method, params }));
    });
}

function once(method, timeout = 20000) {
    return new Promise((resolve, reject) => {
        const set = listeners.get(method) ?? [];
        const fn = (p) => {
            clearTimeout(t);
            const i = set.indexOf(fn);
            if (i >= 0) set.splice(i, 1);
            resolve(p);
        };
        set.push(fn);
        listeners.set(method, set);
        const t = setTimeout(() => {
            const i = set.indexOf(fn);
            if (i >= 0) set.splice(i, 1);
            reject(new Error('timeout ' + method));
        }, timeout);
    });
}

async function navigate(url) {
    const loaded = once('Page.loadEventFired');
    await send('Page.navigate', { url });
    await loaded.catch(() => {});
    await sleep(400);
}

async function metrics(width, height, mobile) {
    await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile });
}

async function shot(name) {
    const { data } = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
    writeFileSync(`${OUT}\\${name}`, Buffer.from(data, 'base64'));
    console.log('shot', name);
}

async function evaluate(expression) {
    const r = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (r.exceptionDetails) throw new Error(JSON.stringify(r.exceptionDetails));
    return r.result?.value;
}

await send('Page.enable');
await send('Runtime.enable');
await send('Network.enable');

// ---- Public pages (Arabic) ----
await metrics(1440, 950, false);
await navigate(BASE + '/');
await shot('home-ar-desktop.png');

await navigate(BASE + '/language/en');
await navigate(BASE + '/');
await shot('home-en-desktop.png');
await navigate(BASE + '/language/ar');

await navigate(BASE + '/about');
await shot('about-ar-desktop.png');

await metrics(375, 844, true);
await navigate(BASE + '/');
await shot('home-ar-mobile.png');
await navigate(BASE + '/login');
await shot('login-ar-mobile.png');

// Functional check: Alpine CSP build drives the mobile nav.
await navigate(BASE + '/');
const alpine = await evaluate('typeof window.Alpine');
const beforeMenu = await evaluate(`document.querySelector('#mobile-menu').getBoundingClientRect().height`);
await evaluate(`document.querySelector('[aria-controls="mobile-menu"]').click()`);
await sleep(300);
const afterMenu = await evaluate(`document.querySelector('#mobile-menu').getBoundingClientRect().height`);
console.log('alpine', alpine, '| mobile menu height', Math.round(beforeMenu), '->', Math.round(afterMenu));

// Demo box must sit above the email form on phones.
await navigate(BASE + '/login');
const demoOrder = await evaluate(`(() => {
    const demo = document.querySelector('.order-1');
    const form = document.querySelector('form[action$="/login"]');
    if (!demo || !form) return 'missing';
    return { demoTop: Math.round(demo.getBoundingClientRect().top), formTop: Math.round(form.getBoundingClientRect().top) };
})()`);
console.log('login demo order (375):', JSON.stringify(demoOrder));

// Arabic typography: no letter-spacing/uppercase on eyebrows or footer headings.
for (const w of [375, 1366]) {
    await metrics(w, w < 768 ? 844 : 900, w < 768);
    await navigate(BASE + '/');
    const typography = await evaluate(`(() => {
        const read = (el) => el ? { ls: getComputedStyle(el).letterSpacing, tt: getComputedStyle(el).textTransform } : null;
        return {
            dir: document.documentElement.dir,
            eyebrow: read(document.querySelector('.eyebrow')),
            footerHeading: read(document.querySelector('footer h2')),
        };
    })()`);
    console.log(`typography ${w}:`, JSON.stringify(typography));
}

// ---- Sign in as demo admin for workspace checks ----
await metrics(1440, 950, false);
await navigate(BASE + '/login');
const token = await evaluate(`document.querySelector('meta[name="csrf-token"]').content`);
const status = await evaluate(
    `fetch('/demo/login',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':${JSON.stringify(token)}},body:'role=admin',credentials:'same-origin'}).then(r=>r.status)`,
);
console.log('demo login status', status);
await sleep(600);

// ---- Sidebar geometry across widths in both locales ----
for (const locale of ['ar', 'en']) {
    await metrics(1440, 950, false);
    await navigate(BASE + '/language/' + locale);
    for (const w of [375, 768, 1024, 1280, 1440]) {
        await metrics(w, 900, w < 1024);
        await navigate(BASE + '/app');
        const rect = await evaluate(`(() => {
            const el = document.querySelector('.app-sidebar');
            if (!el) return null;
            const r = el.getBoundingClientRect();
            return { left: Math.round(r.left), right: Math.round(r.right), width: Math.round(r.width), visible: r.width > 0 && r.left < innerWidth && r.right > 0 };
        })()`);
        console.log(`sidebar ${locale} ${w}px:`, JSON.stringify(rect));
    }
}

// ---- Horizontal overflow at 375px (Arabic) ----
await metrics(1440, 950, false);
await navigate(BASE + '/language/ar');
await metrics(375, 844, false);
const pages = ['/', '/about', '/privacy', '/terms', '/submit', '/track', '/kb', '/login', '/app', '/app/tickets', '/app/tickets/create', '/app/tickets/TKT-2026-0001', '/app/reports', '/app/users', '/app/audit', '/app/settings/business-hours'];
for (const p of pages) {
    await navigate(BASE + p);
    const sw = await evaluate('Math.max(document.documentElement.scrollWidth, document.body.scrollWidth)');
    console.log(`overflow 375 ${p}: ${sw}${sw > 376 ? '  <-- OVERFLOW' : ''}`);
}

// Tap-target audit at 375px (buttons/controls should be >= 44px).
for (const p of ['/', '/app']) {
    await navigate(BASE + p);
    const small = await evaluate(`(() => {
        const els = [...document.querySelectorAll('button, .btn, .btn-primary, .btn-secondary, .btn-ghost, .btn-danger, .btn-teal, .btn-coral, a.nav-link, .app-sidebar a')];
        return els.map((el) => {
            const r = el.getBoundingClientRect();
            return { tag: el.tagName, cls: (el.getAttribute('class') || '').slice(0, 45), h: Math.round(r.height), w: Math.round(r.width), on: r.width > 0 && r.height > 0 };
        }).filter((e) => e.on && (e.h < 44 || e.w < 44));
    })()`);
    console.log(`small targets 375 ${p}:`, JSON.stringify(small));
}

// Diagnose the dashboard at 375px in both emulation modes.
for (const mob of [false, true]) {
    await metrics(375, 844, mob);
    await navigate(BASE + '/app');
    const info = await evaluate(`(() => {
        const all = [...document.querySelectorAll('*')].map((el) => {
            const r = el.getBoundingClientRect();
            return { tag: el.tagName, cls: (el.getAttribute('class') || '').slice(0, 60), left: Math.round(r.left), right: Math.round(r.right), width: Math.round(r.width) };
        });
        const widest = [...all].sort((a, b) => b.width - a.width).slice(0, 6);
        const leftOverflow = all.filter((e) => e.left < -1).sort((a, b) => a.left - b.left).slice(0, 6);
        return { mobile: ${mob}, innerWidth, htmlScroll: document.documentElement.scrollWidth, bodyScroll: document.body.scrollWidth, widest, leftOverflow };
    })()`);
    console.log('diag /app', JSON.stringify(info));
}
await metrics(375, 844, true);
await navigate(BASE + '/app');
await shot('workspace-dashboard-ar-mobile.png');

// ---- Workspace screenshots after the fix ----
await metrics(1024, 800, false);
await navigate(BASE + '/app');
await shot('workspace-dashboard-ar-1024.png');
await navigate(BASE + '/language/en');
await navigate(BASE + '/app');
await shot('workspace-dashboard-en-1024.png');
await navigate(BASE + '/language/ar');

await metrics(1440, 950, false);
await navigate(BASE + '/app');
await shot('workspace-dashboard-ar.png');
await navigate(BASE + '/app/tickets');
await shot('workspace-tickets-ar.png');
await navigate(BASE + '/app/tickets/TKT-2026-0001');
await shot('workspace-ticket-ar.png');

await navigate(BASE + '/language/en');
await navigate(BASE + '/app');
await shot('workspace-dashboard-en.png');

ws.close();
chrome.kill();
sleep(500).then(() => rmSync(PROFILE, { recursive: true, force: true })).catch(() => {});
