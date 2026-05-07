/**
 * SmartHome Resource Management — app.js
 * Global JS utilities loaded in every authenticated page.
 */

'use strict';

// ── CSRF token helper ─────────────────────────────
function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('[name="csrf_token"]')?.value
        || '';
}

// ── Fetch wrapper with CSRF ───────────────────────
async function apiPost(url, body = {}) {
    body.csrf_token = getCsrf();
    const params = new URLSearchParams(body);
    const res    = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
    });
    return res.json();
}

// ── Debounce utility ──────────────────────────────
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

// ── Format number (EGP) ───────────────────────────
function formatEGP(n) {
    return Number(n).toLocaleString('en-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── Auto-dismiss alerts after 5s ─────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert?.close();
        }, 5000);
    });
});
