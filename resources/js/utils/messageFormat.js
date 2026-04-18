import DOMPurify from 'dompurify';
import { marked } from 'marked';

marked.use({
    gfm: true,
    breaks: true,
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
