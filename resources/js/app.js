// HelpDesk — © 2026 Fedaa Mohamed. All rights reserved. Proprietary; not open source.
import Alpine from '@alpinejs/csp';

// Self-hosted OFL fonts (bundled by Vite). Arabic + Latin subsets only.
import '@fontsource/almarai/arabic-400.css';
import '@fontsource/almarai/latin-400.css';
import '@fontsource/almarai/arabic-700.css';
import '@fontsource/almarai/latin-700.css';
import '@fontsource/almarai/arabic-800.css';
import '@fontsource/almarai/latin-800.css';
import '@fontsource/noto-kufi-arabic/arabic-400.css';
import '@fontsource/noto-kufi-arabic/latin-400.css';
import '@fontsource/noto-kufi-arabic/arabic-500.css';
import '@fontsource/noto-kufi-arabic/latin-500.css';
import '@fontsource/noto-kufi-arabic/arabic-700.css';
import '@fontsource/noto-kufi-arabic/latin-700.css';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('copyButton', () => ({
        copied: false,
        async copy() {
            const text = this.$el.closest('[data-copy-text]')?.dataset.copyText ?? '';
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.style.position = 'fixed';
                    area.style.opacity = '0';
                    document.body.appendChild(area);
                    area.select();
                    document.execCommand('copy');
                    document.body.removeChild(area);
                }

                this.copied = true;
                setTimeout(() => (this.copied = false), 1800);
            } catch (error) {
                this.copied = false;
            }
        },
    }));

    Alpine.data('confirmSubmit', () => ({
        submit(event) {
            const message = this.$el.closest('[data-confirm]')?.dataset.confirm ?? '';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        },
    }));

    // File input preview for the public forms.
    Alpine.data('fileList', () => ({
        files: [],
        pick(event) {
            const input = event.target;
            this.files = Array.from(input.files ?? []).map((file) => file.name);
        },
    }));

    // Reply / internal-note tabs and canned-reply insertion in the ticket view.
    Alpine.data('replyComposer', () => ({
        tab: 'reply',
        setTab(name) {
            this.tab = name;
        },
        insertCanned(event) {
            const option = event.target.selectedOptions[0];

            if (!option || !option.value) {
                return;
            }

            const area = event.target.closest('form')?.querySelector('textarea[name="body"]');

            if (!area) {
                return;
            }

            area.value = area.value ? area.value + '\n\n' + option.dataset.body : option.dataset.body;
            area.focus();
            event.target.value = '';
        },
    }));

    // The 419 page "go back" action (globals are not reachable from CSP expressions).
    Alpine.data('historyBack', () => ({
        back() {
            window.history.back();
        },
    }));

    // Workspace shell: mobile sidebar + account menu.
    Alpine.data('workspaceShell', () => ({
        sidebarOpen: false,
        openSidebar() {
            this.sidebarOpen = true;
        },
        closeSidebar() {
            this.sidebarOpen = false;
        },
    }));

    Alpine.data('userMenu', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    }));

    // Public header mobile navigation.
    Alpine.data('publicNav', () => ({
        mobileNav: false,
        toggleMobileNav() {
            this.mobileNav = !this.mobileNav;
        },
    }));

    // Password visibility toggle on the sign-in form.
    Alpine.data('passwordField', () => ({
        visible: false,
        toggle() {
            this.visible = !this.visible;
        },
    }));

    // Mobile filter bottom sheet (ticket list).
    Alpine.data('filterSheet', () => ({
        open: false,
        openSheet() {
            this.open = true;
        },
        closeSheet() {
            this.open = false;
        },
    }));
});

// Press "/" to focus the main search field (ignored while typing).
document.addEventListener('keydown', (event) => {
    if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }

    const active = document.activeElement;
    const tag = active?.tagName;

    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || active?.isContentEditable) {
        return;
    }

    const target = document.querySelector('[data-search-input]');

    if (target) {
        event.preventDefault();
        target.focus();
    }
});

Alpine.start();
