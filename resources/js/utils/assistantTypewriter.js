/** Skip animation for very long replies (keeps UI snappy). */
export const TYPEWRITER_MAX_CHARS = 12000;

const DEFAULT_BATCH = 3;
const DEFAULT_DELAY_MS = 10;

/**
 * Gradually updates visible assistant text for a "live typing" feel.
 * @param {(slice: string) => void} setContent
 * @param {string} fullText
 * @param {{ onScroll?: () => void | Promise<void>, shouldContinue?: () => boolean, batch?: number, delayMs?: number }} [options]
 */
export async function runAssistantTypewriter(setContent, fullText, options = {}) {
    const text = String(fullText ?? '');
    const shouldContinue = options.shouldContinue ?? (() => true);
    const onScroll = options.onScroll;
    const batch = Math.max(1, Number(options.batch) || DEFAULT_BATCH);
    const delayMs = Math.max(0, Number(options.delayMs) || DEFAULT_DELAY_MS);

    if (text.length > TYPEWRITER_MAX_CHARS) {
        setContent(text);
        await onScroll?.();
        return;
    }

    let pos = 0;
    while (pos < text.length) {
        if (!shouldContinue()) {
            return;
        }
        pos = Math.min(text.length, pos + batch + Math.floor(Math.random() * 4));
        setContent(text.slice(0, pos));
        if (pos % 20 === 0 || pos === text.length) {
            await onScroll?.();
        }
        if (delayMs > 0) {
            await new Promise((r) => setTimeout(r, delayMs));
        }
    }
    setContent(text);
    await onScroll?.();
}
