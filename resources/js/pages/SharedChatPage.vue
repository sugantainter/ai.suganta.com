<template>
    <div class="h-dvh overflow-hidden bg-[#0f0f0f] text-zinc-100">
        <div class="flex h-full min-h-0 flex-col overflow-hidden bg-[#212121]">
            <div class="shrink-0 border-b border-zinc-800 px-4 py-3">
                <div class="mx-auto flex w-full max-w-3xl items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <a
                            href="https://www.suganta.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-xl border border-zinc-800 bg-zinc-900/80 p-1.5 shadow-lg shadow-black/30"
                        >
                            <img
                                src="/logo/Su250.png"
                                alt="SuGanta"
                                class="h-7 w-auto rounded-md"
                            >
                        </a>
                        <div>
                            <p class="text-sm font-semibold text-zinc-100">Shared conversation</p>
                            <p class="text-xs text-zinc-500">Public view-only page</p>
                        </div>
                    </div>
                    <a
                        href="/"
                        class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
                    >
                        Login
                    </a>
                </div>
            </div>

            <div ref="messageContainerRef" class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                <div v-if="messages.length" class="mx-auto w-full max-w-3xl px-4 py-8">
                    <div
                        v-for="(message, index) in messages"
                        :key="`${message.role}-${index}-${message.content?.slice(0, 16)}`"
                        class="mb-4"
                    >
                        <div
                            class="whitespace-pre-wrap rounded-2xl px-4 py-3 text-sm leading-6"
                            :class="message.role === 'user'
                                ? 'ml-auto max-w-[86%] bg-zinc-700/70 text-zinc-100'
                                : 'max-w-full bg-zinc-900/70 text-zinc-100'"
                        >
                            {{ message.content }}
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-zinc-700 bg-zinc-900/60 px-4 py-3 text-sm text-zinc-300">
                        <p>Login to access more features and continue this chat.</p>
                        <a href="/" class="mt-2 inline-block text-xs text-emerald-400 hover:text-emerald-300">Login to continue</a>
                    </div>
                </div>
                <div v-else class="flex h-full items-center justify-center px-5">
                    <div class="w-full max-w-3xl text-center">
                        <p class="text-2xl font-medium text-zinc-200">{{ statusText }}</p>
                        <p class="mt-3 text-sm text-zinc-500">Open a valid shared chat link, or login to start your own conversation.</p>
                        <a
                            href="/"
                            class="mt-4 inline-flex items-center rounded-lg border border-zinc-700 bg-zinc-900 px-4 py-2 text-sm text-zinc-100 hover:bg-zinc-800"
                        >
                            Login
                        </a>
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
    document.title = title;
    setMetaTag('meta[name="description"]', 'name', 'description', description);
    setMetaTag('meta[name="keywords"]', 'name', 'keywords', 'shared ai chat, public ai conversation, suganta ai');
    setMetaTag('meta[name="robots"]', 'name', 'robots', 'index, follow');
    setMetaTag('meta[property="og:type"]', 'property', 'og:type', 'article');
    setMetaTag('meta[property="og:title"]', 'property', 'og:title', title);
    setMetaTag('meta[property="og:description"]', 'property', 'og:description', description);
    setMetaTag('meta[property="og:url"]', 'property', 'og:url', canonicalUrl);
    setMetaTag('meta[name="twitter:title"]', 'name', 'twitter:title', title);
    setMetaTag('meta[name="twitter:description"]', 'name', 'twitter:description', description);
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
    loadSharedConversation(route.params.shareToken);
});

watch(
    () => route.params.shareToken,
    async (value) => {
        await loadSharedConversation(value);
    }
);
</script>
