<template>
    <div class="h-dvh overflow-hidden bg-[#0f0f0f] text-zinc-100">
        <div class="flex h-full min-h-0 flex-col overflow-hidden bg-[#212121]">
            <div class="shrink-0 border-b border-zinc-800 bg-[#1b1b1b]/80 px-4 py-3 backdrop-blur">
                <div class="mx-auto flex w-full max-w-4xl items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <a
                            href="https://www.suganta.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-xl border border-zinc-700 bg-zinc-900/80 p-1.5 shadow-lg shadow-black/30"
                        >
                            <img
                                src="/logo/Su250.png"
                                alt="SuGanta"
                                class="h-7 w-auto rounded-md"
                            >
                        </a>
                        <div>
                            <p class="text-sm font-semibold text-zinc-100">Shared conversation</p>
                            <p class="text-xs text-zinc-400">Public view-only page</p>
                        </div>
                    </div>
                    <a
                        href="/"
                        class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs font-medium text-zinc-100 hover:bg-zinc-800"
                    >
                        {{ isAuthenticated ? 'Go to dashboard' : 'Login' }}
                    </a>
                </div>
            </div>

            <div ref="messageContainerRef" class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                <div class="mx-auto w-full max-w-4xl px-3 py-4 sm:px-4 sm:py-6">
                    <div class="mb-4 rounded-2xl border border-zinc-800 bg-zinc-900/40 px-4 py-3">
                        <p class="text-sm text-zinc-300">This is a public snapshot of a conversation.</p>
                        <p class="mt-1 text-xs text-zinc-500">Sign in to continue, upload files, and access advanced tools.</p>
                    </div>

                    <div v-if="messages.length" class="space-y-4">
                    <div
                        v-for="(message, index) in messages"
                        :key="`${message.role}-${index}-${message.content?.slice(0, 16)}`"
                        class="mb-4"
                    >
                        <div
                            class="whitespace-pre-wrap rounded-2xl border border-zinc-800 px-3 py-2.5 text-sm leading-6 shadow-sm shadow-black/20 sm:px-4 sm:py-3"
                            :class="message.role === 'user'
                                ? 'ml-auto max-w-[86%] bg-zinc-700/70 text-zinc-100'
                                : 'max-w-full bg-zinc-900/80 text-zinc-100'"
                        >
                            {{ message.content }}
                        </div>
                    </div>

                        <div class="mt-5 rounded-2xl border border-zinc-700 bg-zinc-900/70 px-4 py-4 text-sm text-zinc-300">
                            <p class="font-medium text-zinc-100">Want to continue this conversation?</p>
                            <p class="mt-1 text-zinc-400">
                                {{ isAuthenticated
                                    ? 'Open chat dashboard to continue with full features.'
                                    : 'Login to access more features and continue with the same chat style.' }}
                            </p>
                            <a
                                href="/"
                                class="mt-3 inline-flex items-center rounded-lg border border-zinc-700 bg-zinc-100 px-3 py-1.5 text-xs font-semibold text-zinc-900 hover:bg-white"
                            >
                                {{ isAuthenticated ? 'Open chat' : 'Login to continue' }}
                            </a>
                        </div>
                    </div>
                    <div v-else class="flex min-h-[55vh] items-center justify-center">
                        <div class="w-full max-w-2xl rounded-2xl border border-zinc-800 bg-zinc-900/50 p-5 text-center sm:p-8">
                            <p class="text-2xl font-semibold text-zinc-100">{{ statusText }}</p>
                            <p class="mt-3 text-sm text-zinc-400">Open a valid shared chat link, or login to start your own conversation.</p>
                            <a
                                href="/"
                                class="mt-5 inline-flex items-center rounded-lg border border-zinc-700 bg-zinc-100 px-4 py-2 text-sm font-semibold text-zinc-900 hover:bg-white"
                            >
                                {{ isAuthenticated ? 'Go to dashboard' : 'Login' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { showErrorAlert } from '../utils/alerts';

const route = useRoute();
const messageContainerRef = ref(null);
const messages = ref([]);
const statusText = ref('Loading shared conversation...');
const isAuthenticated = ref(false);

async function detectAuthState() {
    try {
        const response = await fetch('/api/v1/usage', {
            method: 'GET',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        });
        isAuthenticated.value = response.ok;
    } catch {
        isAuthenticated.value = false;
    }
}

function setMetaTag(selector, attrName, attrValue, content) {
    const tag = document.querySelector(selector);
    if (tag) {
        tag.setAttribute('content', content);
        return;
    }

    const meta = document.createElement('meta');
    meta.setAttribute(attrName, attrValue);
    meta.setAttribute('content', content);
    document.head.appendChild(meta);
}

function applySharedSeo(title, description) {
    const canonicalUrl = `${window.location.origin}${route.fullPath}`;
    const shareImageUrl = `${window.location.origin}/logo/favicon.png`;
    document.title = title;
    setMetaTag('meta[name="description"]', 'name', 'description', description);
    setMetaTag('meta[name="keywords"]', 'name', 'keywords', 'shared ai chat, public ai conversation, suganta ai');
    setMetaTag('meta[name="robots"]', 'name', 'robots', 'index, follow');
    setMetaTag('meta[property="og:type"]', 'property', 'og:type', 'article');
    setMetaTag('meta[property="og:title"]', 'property', 'og:title', title);
    setMetaTag('meta[property="og:description"]', 'property', 'og:description', description);
    setMetaTag('meta[property="og:url"]', 'property', 'og:url', canonicalUrl);
    setMetaTag('meta[property="og:image"]', 'property', 'og:image', shareImageUrl);
    setMetaTag('meta[name="twitter:card"]', 'name', 'twitter:card', 'summary');
    setMetaTag('meta[name="twitter:title"]', 'name', 'twitter:title', title);
    setMetaTag('meta[name="twitter:description"]', 'name', 'twitter:description', description);
    setMetaTag('meta[name="twitter:image"]', 'name', 'twitter:image', shareImageUrl);
}

async function parseApiResponse(response) {
    const rawText = await response.text();
    if (rawText.trim() === '') {
        return {};
    }

    try {
        return JSON.parse(rawText);
    } catch {
        if (response.ok) {
            return { message: rawText };
        }
        throw new Error(rawText || `Request failed: ${response.status}`);
    }
}

async function loadSharedConversation(shareToken) {
    const token = String(shareToken ?? '').trim();
    if (!token) {
        messages.value = [];
        statusText.value = 'Shared conversation not found';
        applySharedSeo('Shared Chat - SuGanta AI', 'This shared conversation is unavailable.');
        return;
    }

    statusText.value = 'Loading shared conversation...';
    try {
        const response = await fetch(`/api/v1/public/chat/share/${encodeURIComponent(token)}?limit=200`, {
            method: 'GET',
            credentials: 'omit',
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await parseApiResponse(response);
        if (!response.ok) {
            throw new Error(data?.message || `Request failed: ${response.status}`);
        }

        messages.value = (data.messages ?? []).map((item) => ({
            role: item.role,
            content: item.content,
        }));
        statusText.value = messages.value.length > 0 ? 'Shared conversation loaded' : 'No messages in this shared chat';

        const subject = String(data.conversation?.subject || 'Shared Chat');
        const title = `${subject} - Shared Chat | SuGanta AI`;
        const description = `Read this shared AI conversation on SuGanta AI.`;
        applySharedSeo(title, description);

        await nextTick();
        if (messageContainerRef.value) {
            messageContainerRef.value.scrollTop = messageContainerRef.value.scrollHeight;
        }
    } catch (error) {
        messages.value = [];
        statusText.value = error?.message || 'Unable to load shared conversation.';
        applySharedSeo('Shared Chat Unavailable - SuGanta AI', statusText.value);
        showErrorAlert(statusText.value, 'Shared chat unavailable');
    }
}

onMounted(() => {
    detectAuthState();
    loadSharedConversation(route.params.shareToken);
});

watch(
    () => route.params.shareToken,
    async (value) => {
        await loadSharedConversation(value);
    }
);
</script>
