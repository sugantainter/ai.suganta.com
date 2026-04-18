import DOMPurify from 'dompurify';
import { marked, Renderer } from 'marked';

function escapeHtmlAttr(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const baseRenderer = new Renderer();

marked.use({
    gfm: true,
    breaks: true,
    renderer: {
        code(token) {
            const body = baseRenderer.code.call(baseRenderer, token);
            const lang = String(token.lang ?? '').trim();
            const label = lang !== '' ? lang : 'Code';
            const preClass = 'm-0 max-h-[min(70vh,520px)] overflow-auto rounded-none border-0 bg-transparent p-4 font-mono text-[13px] leading-relaxed text-zinc-100';
            const wrappedPre = body.replace('<pre>', `<pre class="${preClass}">`);
            return `<div class="md-code-frame my-4 overflow-hidden rounded-xl border border-zinc-700/85 bg-[#161616] shadow-md shadow-black/40">`
                + '<div class="flex items-center justify-between gap-2 border-b border-zinc-800/90 bg-zinc-900/95 px-3 py-2">'
                + `<span class="truncate font-mono text-[11px] font-medium uppercase tracking-wide text-zinc-500">${escapeHtmlAttr(label)}</span>`
                + '<button type="button" class="md-code-copy shrink-0 rounded-lg border border-zinc-600/90 bg-zinc-800 px-2.5 py-1 text-[11px] font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-700" data-md-code-copy>Copy</button>'
                + `</div><div class="overflow-x-auto">${wrappedPre}</div></div>`;
        },
        hr() {
            return '<hr class="my-8 border-0 border-t border-zinc-600/50" />';
        },
        blockquote(token) {
            const html = baseRenderer.blockquote.call(baseRenderer, token);
            return html.replace(
                '<blockquote>',
                '<blockquote class="my-4 border-l-[3px] border-zinc-500 bg-zinc-900/35 py-2 pl-4 pr-3 text-zinc-300 [&_p]:my-1">',
            );
        },
        heading(token) {
            const html = baseRenderer.heading.call(this, token);
            const depth = Math.min(6, Math.max(1, Number(token.depth) || 1));
            const classes = {
                1: 'mt-8 mb-3 text-2xl font-bold tracking-tight text-zinc-50 first:mt-0',
                2: 'mt-6 mb-2 text-xl font-semibold text-zinc-100',
                3: 'mt-5 mb-1.5 text-lg font-semibold text-zinc-100',
                4: 'mt-4 mb-1 text-base font-semibold text-zinc-100',
                5: 'mt-3 mb-1 text-sm font-semibold text-zinc-200',
                6: 'mt-3 mb-1 text-sm font-medium text-zinc-300',
            };
            const cls = classes[depth] ?? classes[3];
            return html.replace(`<h${depth}>`, `<h${depth} class="${cls}">`);
        },
    },
});

let linkTargetHookInstalled = false;

function ensureExternalLinkHook() {
    if (linkTargetHookInstalled || typeof DOMPurify.addHook !== 'function') {
        return;
    }
    DOMPurify.addHook('afterSanitizeAttributes', (node) => {
        if (node.tagName === 'A') {
            node.setAttribute('target', '_blank');
            const rel = String(node.getAttribute('rel') || '');
            if (!/\bnoopener\b/.test(rel)) {
                node.setAttribute('rel', rel ? `${rel} noopener noreferrer` : 'noopener noreferrer');
            }
        }
    });
    linkTargetHookInstalled = true;
}

/**
 * Render assistant/user markdown to safe HTML for v-html.
 * @param {unknown} content
 * @returns {string}
 */
export function formatMessageContent(content) {
    const raw = String(content ?? '').replace(/\r\n/g, '\n').trim();
    if (raw === '') {
        return '';
    }

    ensureExternalLinkHook();

    try {
        const dirty = marked.parse(raw);
        return DOMPurify.sanitize(String(dirty));
    } catch {
        return DOMPurify.sanitize(raw.replace(/</g, '&lt;').replace(/>/g, '&gt;'));
    }
}

/**
 * Copy fenced code when user clicks a `.md-code-copy` control (event delegation).
 * @param {MouseEvent} event
 * @returns {boolean} true if a copy action was handled
 */
export function handleMarkdownCodeCopyClick(event) {
    const target = event.target;
    if (!(target instanceof Element)) {
        return false;
    }
    const btn = target.closest('[data-md-code-copy]');
    if (!btn || !(btn instanceof HTMLButtonElement)) {
        return false;
    }
    const frame = btn.closest('.md-code-frame');
    const codeEl = frame?.querySelector('pre code') ?? frame?.querySelector('code');
    const text = codeEl?.textContent ?? '';
    if (text === '') {
        return true;
    }
    void navigator.clipboard?.writeText(text).then(() => {
        const prev = btn.textContent;
        btn.textContent = 'Copied';
        window.setTimeout(() => {
            btn.textContent = prev || 'Copy';
        }, 1600);
    }).catch(() => {});
    return true;
}
