import { loginGatewayRedirectIfNeeded } from './authRedirect';

function parseJsonSafe(text) {
    try {
        return JSON.parse(text);
    } catch {
        return null;
    }
}

/**
 * @param {string} rawEvent
 * @param {{ onDelta?: (d: string) => void, onMessage?: (m: object) => void }} handlers
 */
function dispatchSseEvent(rawEvent, handlers) {
    const lines = rawEvent.split('\n').map((l) => l.trimEnd()).filter((l) => l.length > 0);
    for (const line of lines) {
        if (!line.startsWith('data:')) {
            continue;
        }
        const payloadStr = line.startsWith('data: ') ? line.slice(6).trim() : line.slice(5).trim();
        if (payloadStr === '' || payloadStr === '[DONE]') {
            continue;
        }
        const evt = parseJsonSafe(payloadStr);
        if (!evt || typeof evt !== 'object') {
            continue;
        }
        if (evt.type === 'delta' && evt.content) {
            handlers.onDelta?.(String(evt.content));
        }
        if (evt.type === 'message') {
            handlers.onMessage?.(evt);
        }
        if (evt.type === 'error') {
            const err = new Error(String(evt.message || 'Chat stream failed'));
            err.code = String(evt.code || '');
            err.status = 422;
            if (evt.job_id) {
                err.job_id = String(evt.job_id);
            }
            if (evt.status) {
                err.async_status = evt.status;
            }
            throw err;
        }
    }
}

/**
 * POST /api/v1/chat with stream:true; reads SSE until [DONE].
 *
 * @param {string} path
 * @param {object} payload
 * @param {{ onDelta?: (chunk: string) => void, timeoutMs?: number, signal?: AbortSignal }} [options]
 * @returns {Promise<object>}
 */
export async function fetchChatSseJson(path, payload, options = {}) {
    const timeoutMs = Number(options.timeoutMs ?? 0);
    const hasCustomSignal = Boolean(options.signal);
    const controller = !hasCustomSignal && timeoutMs > 0 ? new AbortController() : null;
    const timeoutId = controller
        ? setTimeout(() => {
            controller.abort();
        }, timeoutMs)
        : null;

    let response;
    try {
        response = await fetch(path, {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'text/event-stream',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ...payload, stream: true }),
            signal: options.signal ?? controller?.signal,
        });
    } catch (cause) {
        const aborted = controller?.signal?.aborted || String(cause?.name || '').toLowerCase() === 'aborterror';
        const error = new Error(
            aborted
                ? 'Request timed out. Switching to background processing...'
                : (String(cause?.message || '') || 'Network request failed.'),
        );
        error.code = aborted ? 'request_timeout' : 'network_error';
        error.status = 0;
        throw error;
    } finally {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
    }

    if (!response.ok) {
        const rawText = await response.text();
        const data = parseJsonSafe(rawText.trim()) || {};
        if (loginGatewayRedirectIfNeeded(response, data)) {
            return new Promise(() => {});
        }
        const err = new Error(data?.message || rawText || `Request failed: ${response.status}`);
        err.code = String(data?.code || '');
        err.status = Number(response.status || 0);
        throw err;
    }

    const reader = response.body?.getReader();
    if (!reader) {
        throw new Error('Streaming is not supported in this environment.');
    }

    const decoder = new TextDecoder();
    let buffer = '';
    /** @type {object|null} */
    let finalMessage = null;

    const handlers = {
        onDelta: options.onDelta,
        onMessage: (evt) => {
            finalMessage = evt;
        },
    };

    while (true) {
        const { done, value } = await reader.read();
        if (done) {
            break;
        }
        buffer += decoder.decode(value, { stream: true });
        let sep;
        while ((sep = buffer.indexOf('\n\n')) !== -1) {
            const rawEvent = buffer.slice(0, sep);
            buffer = buffer.slice(sep + 2);
            dispatchSseEvent(rawEvent, handlers);
        }
    }

    if (buffer.trim() !== '') {
        dispatchSseEvent(buffer, handlers);
    }

    if (!finalMessage) {
        throw new Error('Stream ended without a final message.');
    }

    return {
        conversation_id: finalMessage.conversation_id ?? null,
        provider: finalMessage.provider ?? '',
        model: finalMessage.model ?? '',
        message: finalMessage.message ?? finalMessage.content ?? '',
        usage: finalMessage.usage || {},
        _streamed: true,
    };
}
