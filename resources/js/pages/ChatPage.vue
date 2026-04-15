<template>
    <div class="h-dvh overflow-hidden bg-[#0f0f0f] text-zinc-100">
        <div class="grid h-full min-h-0" :class="isSharedView ? 'grid-cols-1' : 'md:grid-cols-[260px_1fr]'">
            <aside v-if="!isSharedView" class="hidden h-full min-h-0 flex-col border-r border-zinc-800 bg-[#171717] md:flex">
                <div class="shrink-0 space-y-3 border-b border-zinc-800 p-3">
                    <a
                        href="https://www.suganta.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center justify-center rounded-xl border border-zinc-800 bg-zinc-900/80 p-2 shadow-lg shadow-black/30"
                    >
                        <img
                            src="/logo/Su250.png"
                            alt="SuGanta"
                            class="h-8 w-auto rounded-md"
                        >
                    </a>
                    <div class="flex items-center rounded-lg border border-zinc-800 bg-zinc-900/70 p-1">
                        <RouterLink
                            to="/"
                            class="w-full rounded-md px-3 py-1.5 text-center text-xs font-medium text-zinc-300 transition hover:text-white"
                            active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                        >
                            Chat
                        </RouterLink>
                        <RouterLink
                            to="/settings"
                            class="w-full rounded-md px-3 py-1.5 text-center text-xs font-medium text-zinc-300 transition hover:text-white"
                            active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                        >
                            Settings
                        </RouterLink>
                    </div>
                    <button
                        class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-left text-sm font-medium hover:bg-zinc-700"
                        @click="startNewChat"
                    >
                        + New chat
                    </button>
                    <button
                        class="w-full rounded-lg px-3 py-2 text-left text-sm text-zinc-300 hover:bg-zinc-800"
                        type="button"
                        @click="openSearchModal"
                    >
                        Search chats
                    </button>
                </div>

                <div class="hide-scrollbar min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 py-2" @scroll="handleHistoryScroll">
                    <p class="px-2 py-1 text-[11px] uppercase tracking-wide text-zinc-500">Recent chats</p>

                    <button
                        v-for="conversation in conversations"
                        :key="conversation.id"
                        class="mb-1 w-full rounded-lg px-3 py-2 text-left text-sm transition"
                        :class="currentConversationId === conversation.id
                            ? 'bg-zinc-800 text-white'
                            : 'text-zinc-300 hover:bg-zinc-800/70'"
                        @click="openConversation(conversation.id)"
                    >
                        <p class="truncate font-medium">{{ conversation.subject || 'Untitled chat' }}</p>
                        <p class="mt-1 truncate text-xs text-zinc-500">{{ conversation.last_assistant_message || 'No reply yet' }}</p>
                    </button>

                    <p v-if="!conversations.length" class="px-3 py-2 text-xs text-zinc-500">No conversations yet.</p>
                    <p v-if="historyLoading" class="px-3 py-2 text-xs text-zinc-500">Loading history...</p>
                    <p v-else-if="!historyHasMore && conversations.length" class="px-3 py-2 text-xs text-zinc-600">
                        No more chats
                    </p>
                </div>

                <div class="shrink-0 border-t border-zinc-800 px-3 py-3 text-xs text-zinc-500">
                    {{ usage.total_tokens ?? 0 }} / {{ usage.token_limit ?? 10000 }} tokens
                </div>
            </aside>

            <section class="flex h-full min-h-0 flex-col overflow-hidden bg-[#212121]">
                <div class="sticky top-0 z-30 bg-[#212121]">
                    <div class="shrink-0 border-b border-zinc-800 px-3 py-2 md:hidden">
                        <div class="flex items-center justify-between gap-2">
                            <a
                                href="https://www.suganta.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border border-zinc-800 bg-zinc-900/80 p-1.5"
                            >
                                <img
                                    src="/logo/Su250.png"
                                    alt="SuGanta"
                                    class="h-6 w-auto rounded-md"
                                >
                            </a>
                            <div class="flex items-center rounded-lg border border-zinc-800 bg-zinc-900/70 p-1">
                                <RouterLink
                                    to="/"
                                    class="rounded-md px-3 py-1 text-xs font-medium text-zinc-300"
                                    active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                                >
                                    Chat
                                </RouterLink>
                                <RouterLink
                                    to="/settings"
                                    class="rounded-md px-3 py-1 text-xs font-medium text-zinc-300"
                                    active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                                >
                                    Settings
                                </RouterLink>
                            </div>
                        </div>
                    </div>
                    <ChatTopBar
                        v-model="model"
                        :capability-filter="capabilityFilter"
                        :is-shared-view="isSharedView"
                        :model-options="modelOptions"
                        :status-text="statusText"
                        :model-error-message="modelErrorMessage"
                        :share-loading="shareLoading"
                        :can-share="Boolean(currentConversationId)"
                        @update:capability-filter="capabilityFilter = $event"
                        @open-search="openSearchModal"
                        @share="shareConversation"
                    />
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
                                <div v-if="message.processing" class="flex items-center gap-2 text-zinc-300">
                                    <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-zinc-400"></span>
                                    <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-zinc-400 [animation-delay:140ms]"></span>
                                    <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-zinc-400 [animation-delay:280ms]"></span>
                                    <span class="ml-1 text-xs text-zinc-400">AI is processing...</span>
                                </div>
                                <div
                                    v-else
                                    class="space-y-2 wrap-break-word"
                                    v-html="formatMessageContent(message.content)"
                                ></div>
                            </div>
                            <div
                                v-if="message.attachments?.length"
                                class="mt-2 flex flex-wrap gap-2"
                                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    v-for="attachment in message.attachments"
                                    :key="`${attachment.name}-${attachment.size || 0}`"
                                    class="flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-900/80 px-2 py-1 text-xs text-zinc-300"
                                >
                                    <img
                                        v-if="attachment.type?.startsWith('image/') && attachment.dataUrl"
                                        :src="attachment.dataUrl"
                                        :alt="attachment.name"
                                        class="h-8 w-8 rounded object-cover"
                                    >
                                    <div
                                        v-else
                                        class="flex h-8 w-8 items-center justify-center rounded bg-zinc-700 text-[10px] font-semibold text-zinc-200"
                                    >
                                        FILE
                                    </div>
                                    <span class="max-w-44 truncate">{{ attachment.name }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="isSharedView" class="mt-4 rounded-xl border border-zinc-700 bg-zinc-900/60 px-4 py-3 text-sm text-zinc-300">
                            <p>Login to access more features and continue this chat.</p>
                            <a href="/" class="mt-2 inline-block text-xs text-emerald-400 hover:text-emerald-300">Login to continue</a>
                        </div>
                    </div>
                    <div v-else class="flex h-full items-center justify-center px-5">
                        <div class="w-full max-w-3xl text-center">
                            <p class="text-3xl font-medium text-zinc-200">How can I help you today?</p>
                            <p class="mt-3 text-sm text-zinc-500">Start a new conversation and ask anything.</p>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-zinc-800 bg-[#212121] px-4 py-4">
                    <div class="mx-auto w-full max-w-3xl">
                        <ConversationUploadsModal
                            :open="uploadsModalOpen"
                            :can-open="Boolean(currentConversationId)"
                            :loading="assetsLoading"
                            :assets="conversationAssets"
                            :asset-action-loading-id="assetActionLoadingId"
                            @open="uploadsModalOpen = true"
                            @close="uploadsModalOpen = false"
                            @preview="openAsset($event, false)"
                            @download="openAsset($event, true)"
                        />
                        <div
                            v-if="chatErrorMessage"
                            class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-300"
                        >
                            {{ chatErrorMessage }}
                        </div>
                        <div class="rounded-[1.6rem] border border-zinc-700 bg-zinc-900 px-3 py-3 sm:rounded-full sm:py-2">
                            <div v-if="attachments.length && !isSharedView" class="mb-3 flex flex-wrap gap-2">
                                <div
                                    v-for="item in attachments"
                                    :key="`${item.name}-${item.size}`"
                                    class="flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-800/80 px-2 py-1 text-xs text-zinc-300"
                                >
                                    <img
                                        v-if="item.type.startsWith('image/') && item.dataUrl"
                                        :src="item.dataUrl"
                                        :alt="item.name"
                                        class="h-8 w-8 rounded object-cover"
                                    >
                                    <div
                                        v-else
                                        class="flex h-8 w-8 items-center justify-center rounded bg-zinc-700 text-[10px] font-semibold text-zinc-200"
                                    >
                                        FILE
                                    </div>
                                    <span class="max-w-44 truncate">{{ item.name }}</span>
                                    <button
                                        class="text-zinc-400 hover:text-zinc-200"
                                        type="button"
                                        @click="removeAttachment(item)"
                                    >
                                        x
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    class="hidden"
                                    accept="image/*,.txt,.md,.csv,.json,.log"
                                    multiple
                                    @change="onFilePicked"
                                >
                                <button
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-300 hover:bg-zinc-800 hover:text-zinc-100 sm:h-9 sm:w-9"
                                    type="button"
                                    :disabled="isSharedView"
                                    @click="openFilePicker"
                                    title="Upload files"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                                        <path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6z"></path>
                                    </svg>
                                </button>
                                <textarea
                                    ref="composerInputRef"
                                    v-model="inputMessage"
                                    rows="1"
                                    :disabled="isSharedView"
                                    class="max-h-44 min-h-12 w-full resize-none bg-transparent py-2.5 text-sm text-zinc-100 outline-none placeholder:text-zinc-500 sm:max-h-40 sm:min-h-9 sm:py-2"
                                    :placeholder="isSharedView ? 'Login to continue this chat' : 'Ask anything'"
                                    @keydown.enter.exact.prevent="sendMessage"
                                />
                                <button
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-300 transition disabled:opacity-60 sm:h-9 sm:w-9"
                                    :class="listening
                                        ? 'animate-pulse bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/50'
                                        : 'hover:bg-zinc-800 hover:text-zinc-100'"
                                    type="button"
                                    :disabled="isSharedView || !speechSupported"
                                    @click="toggleMic"
                                    :title="listening ? 'Stop microphone' : 'Use microphone'"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                                        <path d="M12 15a3 3 0 0 0 3-3V7a3 3 0 1 0-6 0v5a3 3 0 0 0 3 3zm5-3a5 5 0 1 1-10 0H5a7 7 0 0 0 6 6.93V21h2v-2.07A7 7 0 0 0 19 12z"></path>
                                    </svg>
                                </button>
                                <button
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-zinc-900 hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-60 sm:h-9 sm:w-9"
                                    :disabled="isSharedView || sending || (!inputMessage.trim() && attachments.length === 0)"
                                    @click="sendMessage"
                                    :title="sending ? 'Sending' : 'Send message'"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                                        <path d="M3 11.5 21 4l-7.5 18-1.9-7.6L3 11.5zm9 1.6 1.1 4.3L17.8 6l-8.6 7.1z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div
                                v-if="listening && !isSharedView"
                                class="mt-2 flex items-center gap-2 pl-1 text-[11px] text-emerald-300"
                            >
                                <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                                <span>Listening... speak now</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <ShareChatModal
            :open="shareModalOpen"
            :title="shareModalTitle"
            :url="sharedUrlText"
            :copied-text="shareCopiedText"
            @close="closeShareModal"
            @copy="copyShareLink"
            @share-platform="shareOnPlatform"
        />

        <ChatSearchModal
            :open="searchModalOpen"
            :query="searchQuery"
            :loading="searchLoading"
            :error="searchError"
            :conversations="filteredConversations"
            @close="closeSearchModal"
            @update:query="searchQuery = $event"
            @open-conversation="openConversationFromSearch"
        />

    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { showErrorAlert } from '../utils/alerts';
import ChatTopBar from '../components/chat/ChatTopBar.vue';
import ShareChatModal from '../components/chat/ShareChatModal.vue';
import ChatSearchModal from '../components/chat/ChatSearchModal.vue';
import ConversationUploadsModal from '../components/chat/ConversationUploadsModal.vue';

const route = useRoute();
const router = useRouter();
const messageContainerRef = ref(null);
const fileInputRef = ref(null);
const composerInputRef = ref(null);

const conversations = ref([]);
const messages = ref([]);
const usage = ref({ total_tokens: 0, recent_requests: [] });
const models = ref([]);

const searchQuery = ref('');
const searchModalOpen = ref(false);
const searchResults = ref([]);
const searchLoading = ref(false);
const searchError = ref('');
let searchDebounceTimer = null;
let activeSearchRequestId = 0;

const currentConversationId = ref(null);
const model = ref('gemini-2.5-flash-lite');
const temperature = ref(0.7);
const maxTokens = ref(512);
const capabilityFilter = ref('all');
const inputMessage = ref('');
const attachments = ref([]);
const sending = ref(false);
const statusText = ref('Ready');
const modelErrorMessage = ref('');
const chatErrorMessage = ref('');
const conversationAssets = ref([]);
const assetsLoading = ref(false);
const assetActionLoadingId = ref(null);
const uploadsModalOpen = ref(false);
const historyLoading = ref(false);
const historyPage = ref(1);
const historyHasMore = ref(true);
const shareLoading = ref(false);
const sharedUrlText = ref('');
const shareModalOpen = ref(false);
const shareCopiedText = ref('Copy');
const shareModalTitle = ref('Share conversation');
const SpeechRecognitionCtor = typeof window !== 'undefined'
    ? (window.SpeechRecognition || window.webkitSpeechRecognition || null)
    : null;
const speechSupported = Boolean(SpeechRecognitionCtor);
const listening = ref(false);
let recognition = null;
const isSharedView = computed(() => route.name === 'chat.shared');
const shareTokenFromRoute = computed(() => String(route.params.shareToken ?? '').trim());

const modelOptions = computed(() => {
    if (capabilityFilter.value === 'all') {
        return models.value;
    }
    if (capabilityFilter.value === 'vision') {
        return models.value.filter((item) => item.supports_vision === true);
    }
    if (capabilityFilter.value === 'reasoning') {
        return models.value.filter((item) => item.supports_reasoning === true);
    }
    if (capabilityFilter.value === 'web_search') {
        return models.value.filter((item) => item.supports_web_search === true);
    }
    if (capabilityFilter.value === 'tools') {
        return models.value.filter((item) => item.supports_tools === true);
    }
    return models.value;
});

const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) {
        return conversations.value;
    }
    return searchResults.value;
});

const currentConversationSubject = computed(() => {
    const conversationId = currentConversationId.value;
    if (!conversationId) {
        return '';
    }
    const match = conversations.value.find((item) => Number(item?.id) === Number(conversationId));
    if (!match) {
        return '';
    }
    return String(match.subject || '').trim();
});

watch([capabilityFilter, models], () => {
    if (isSharedView.value) {
        modelErrorMessage.value = '';
        return;
    }

    if (!modelOptions.value.some((item) => item.model === model.value)) {
        model.value = modelOptions.value[0]?.model ?? '';
        if (models.value.length > 0 && model.value === '') {
            modelErrorMessage.value = 'No models match the selected capability filter.';
        } else if (models.value.length === 0) {
            modelErrorMessage.value = 'No models are available for your account right now.';
        } else {
            modelErrorMessage.value = '';
        }
    } else {
        modelErrorMessage.value = '';
    }
});

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

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatInlineMarkup(value) {
    return String(value ?? '')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code class="rounded bg-zinc-800 px-1 py-0.5 text-[11px] text-zinc-100">$1</code>');
}

function formatMessageContent(content) {
    const escaped = escapeHtml(content).replace(/\r\n/g, '\n').trim();
    if (escaped === '') {
        return '';
    }

    const blocks = escaped.split(/\n{2,}/);
    const htmlBlocks = blocks.map((block) => {
        const lines = block.split('\n').map((line) => line.trim()).filter((line) => line !== '');
        if (lines.length === 0) {
            return '';
        }

        const bulletPattern = /^[-*•]\s+/;
        const numberedPattern = /^\d+\.\s+/;
        if (lines.every((line) => bulletPattern.test(line))) {
            const items = lines
                .map((line) => `<li>${formatInlineMarkup(line.replace(bulletPattern, ''))}</li>`)
                .join('');
            return `<ul class="list-disc space-y-1 pl-5">${items}</ul>`;
        }
        if (lines.every((line) => numberedPattern.test(line))) {
            const items = lines
                .map((line) => `<li>${formatInlineMarkup(line.replace(numberedPattern, ''))}</li>`)
                .join('');
            return `<ol class="list-decimal space-y-1 pl-5">${items}</ol>`;
        }

        const renderedLines = lines.map((line) => {
            const heading = line.match(/^#{1,3}\s+(.+)$/);
            if (heading) {
                return `<p class="font-semibold text-zinc-100">${formatInlineMarkup(heading[1])}</p>`;
            }
            return `<p>${formatInlineMarkup(line)}</p>`;
        }).join('');

        return `<div class="space-y-1">${renderedLines}</div>`;
    }).filter((block) => block !== '');

    return htmlBlocks.join('');
}

async function apiRequest(path, options = {}) {
    const response = await fetch(path, {
        credentials: 'include',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(options.headers ?? {}),
        },
    });

    const data = await parseApiResponse(response);
    if (!response.ok) {
        throw new Error(data?.message || `Request failed: ${response.status}`);
    }

    return data;
}

function parseConversationId(value) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    if (!Number.isFinite(parsed) || parsed <= 0) {
        return null;
    }
    return parsed;
}

async function syncConversationRoute(conversationId) {
    if (isSharedView.value) {
        return;
    }

    const parsed = parseConversationId(conversationId);
    if (parsed === null) {
        if (route.name !== 'chat.home') {
            await router.push({ name: 'chat.home' });
        }
        return;
    }

    if (route.name === 'chat.conversation' && parseConversationId(route.params.conversationId) === parsed) {
        return;
    }

    await router.push({ name: 'chat.conversation', params: { conversationId: String(parsed) } });
}

async function scrollMessagesToBottom() {
    await nextTick();
    const el = messageContainerRef.value;
    if (!el) {
        return;
    }
    el.scrollTop = el.scrollHeight;
}

async function focusComposer() {
    await nextTick();
    composerInputRef.value?.focus();
}

async function loadBootstrapData() {
    if (isSharedView.value) {
        return;
    }

    statusText.value = 'Loading...';
    modelErrorMessage.value = '';
    try {
        const [usageData, modelData] = await Promise.all([
            apiRequest('/api/v1/usage'),
            apiRequest('/api/v1/models'),
        ]);

        usage.value = usageData ?? { total_tokens: 0, recent_requests: [] };
        models.value = modelData.models ?? [];
        if (models.value.length === 0) {
            modelErrorMessage.value = 'No models are available right now. Please try again later.';
        }
        await loadConversationList(true);
        const first = modelOptions.value[0];
        if (first) {
            model.value = first.model;
            modelErrorMessage.value = '';
        }
        statusText.value = 'Ready';
    } catch (error) {
        modelErrorMessage.value = 'Unable to load models. Please refresh or try again shortly.';
        statusText.value = error.message || 'Failed to load data';
        showErrorAlert(statusText.value, 'Failed to load chat data');
    }
}

async function openConversation(conversationId, syncRoute = true) {
    if (isSharedView.value) {
        return;
    }

    const parsedConversationId = parseConversationId(conversationId);
    if (parsedConversationId === null) {
        return;
    }

    currentConversationId.value = parsedConversationId;
    chatErrorMessage.value = '';
    statusText.value = 'Loading conversation...';
    try {
        const data = await apiRequest(`/api/v1/chat/history/${parsedConversationId}?limit=200`);
        messages.value = (data.messages ?? []).map((item) => ({
            role: item.role,
            content: item.content,
            attachments: [],
            processing: false,
        }));
        if (syncRoute) {
            await syncConversationRoute(parsedConversationId);
        }
        await loadConversationAssets(parsedConversationId);
        statusText.value = 'Conversation loaded';
        await scrollMessagesToBottom();
        await focusComposer();
    } catch (error) {
        statusText.value = error.message || 'Failed to load conversation';
        showErrorAlert(statusText.value, 'Conversation load failed');
    }
}

async function startNewChat() {
    if (isSharedView.value) {
        return;
    }

    currentConversationId.value = null;
    messages.value = [];
    conversationAssets.value = [];
    uploadsModalOpen.value = false;
    inputMessage.value = '';
    chatErrorMessage.value = '';
    await syncConversationRoute(null);
    statusText.value = 'New chat started';
    await focusComposer();
}

function openSearchModal() {
    if (isSharedView.value) {
        return;
    }

    searchQuery.value = '';
    searchResults.value = [];
    searchError.value = '';
    searchModalOpen.value = true;
}

function closeSearchModal() {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
    searchModalOpen.value = false;
}

async function openConversationFromSearch(conversationId) {
    await openConversation(conversationId);
    closeSearchModal();
}

async function runSearchFromDatabase(query) {
    const trimmed = String(query ?? '').trim();
    if (trimmed === '') {
        searchResults.value = conversations.value;
        searchLoading.value = false;
        searchError.value = '';
        return;
    }

    const requestId = ++activeSearchRequestId;
    searchLoading.value = true;
    searchError.value = '';
    try {
        const data = await apiRequest(`/api/v1/chat/histories/search?q=${encodeURIComponent(trimmed)}&limit=30&page=1`);
        if (requestId !== activeSearchRequestId) {
            return;
        }
        searchResults.value = data.conversations ?? [];
    } catch (error) {
        if (requestId !== activeSearchRequestId) {
            return;
        }
        searchResults.value = [];
        searchError.value = error.message || 'Failed to search chat history';
        showErrorAlert(searchError.value, 'Search failed');
    } finally {
        if (requestId === activeSearchRequestId) {
            searchLoading.value = false;
        }
    }
}

async function sendMessage() {
    if (isSharedView.value) {
        await showErrorAlert('Please login to continue this conversation with full features.', 'Login required');
        return;
    }

    const text = inputMessage.value.trim();
    if ((!text && attachments.value.length === 0) || sending.value) {
        return;
    }

    if (!model.value) {
        modelErrorMessage.value = 'Please select a model before sending your message.';
        chatErrorMessage.value = '';
        statusText.value = 'Model selection required';
        return;
    }

    const currentAttachments = [...attachments.value];
    const attachmentSummary = currentAttachments.length > 0
        ? `\n\nAttachments: ${currentAttachments.map((item) => item.name).join(', ')}`
        : '';
    const composedUserText = `${text}${attachmentSummary}`.trim();
    const nextMessages = [...messages.value, {
        role: 'user',
        content: composedUserText,
        attachments: currentAttachments,
        processing: false,
    }];
    messages.value = [...nextMessages, {
        role: 'assistant',
        content: '',
        attachments: [],
        processing: true,
    }];
    inputMessage.value = '';
    attachments.value = [];
    sending.value = true;
    chatErrorMessage.value = '';
    statusText.value = 'Sending...';
    await scrollMessagesToBottom();

    try {
        const payload = {
            model: model.value,
            conversation_id: currentConversationId.value ?? undefined,
            save_history: true,
            stream: false,
            temperature: Number(temperature.value),
            max_tokens: Number(maxTokens.value),
            messages: nextMessages,
            attachments: currentAttachments.map((item) => ({
                name: String(item.name || 'attachment'),
                type: item.type?.startsWith('image/') ? 'image' : (item.textContent ? 'text' : 'file'),
                mime_type: String(item.type || 'application/octet-stream'),
                content_text: item.textContent ? String(item.textContent) : undefined,
                content_base64: item.dataUrl ? String(item.dataUrl) : undefined,
            })),
        };

        const data = await apiRequest('/api/v1/chat', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        if (data.conversation_id) {
            currentConversationId.value = data.conversation_id;
            await syncConversationRoute(data.conversation_id);
            await loadConversationAssets(data.conversation_id);
        }

        messages.value = [...nextMessages, {
            role: 'assistant',
            content: data.message ?? '',
            attachments: [],
            processing: false,
        }];
        await Promise.all([loadConversationList(), loadUsage()]);
        statusText.value = 'Response received';
        chatErrorMessage.value = '';
        await scrollMessagesToBottom();
    } catch (error) {
        messages.value = nextMessages;
        chatErrorMessage.value = error.message || 'Unable to process chat request at this time.';
        modelErrorMessage.value = String(error.message || '').toLowerCase().includes('model')
            ? (error.message || 'Model error. Please choose another model and try again.')
            : modelErrorMessage.value;
        statusText.value = error.message || 'Failed to send message';
        showErrorAlert(chatErrorMessage.value, 'Chat request failed');
    } finally {
        sending.value = false;
    }
}

function openFilePicker() {
    if (isSharedView.value) {
        return;
    }

    fileInputRef.value?.click();
}

async function onFilePicked(event) {
    const input = event.target;
    const files = Array.from(input?.files ?? []);
    if (files.length === 0) {
        return;
    }

    for (const file of files) {
        const item = {
            name: file.name,
            size: file.size,
            type: file.type || 'application/octet-stream',
            textContent: '',
            dataUrl: '',
        };

        try {
            if (item.type.startsWith('image/')) {
                item.dataUrl = await readFileAsDataUrl(file);
            } else if (
                item.type.startsWith('text/') ||
                file.name.endsWith('.md') ||
                file.name.endsWith('.csv') ||
                file.name.endsWith('.json') ||
                file.name.endsWith('.log')
            ) {
                item.textContent = (await file.text()).slice(0, 15000);
            }
            attachments.value.push(item);
        } catch {
            chatErrorMessage.value = `Unable to read file: ${file.name}`;
            showErrorAlert(chatErrorMessage.value, 'File read failed');
        }
    }

    input.value = '';
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Failed to read file.'));
        reader.readAsDataURL(file);
    });
}

function removeAttachment(item) {
    attachments.value = attachments.value.filter((entry) => !(entry.name === item.name && entry.size === item.size));
}

async function loadConversationAssets(conversationId) {
    const parsedConversationId = parseConversationId(conversationId);
    if (parsedConversationId === null) {
        conversationAssets.value = [];
        return;
    }

    assetsLoading.value = true;
    try {
        const data = await apiRequest(`/api/v1/chat/history/${parsedConversationId}/assets?limit=100`);
        conversationAssets.value = data.assets ?? [];
    } catch {
        conversationAssets.value = [];
    } finally {
        assetsLoading.value = false;
    }
}

async function openAsset(asset, forceDownload = false) {
    const conversationId = currentConversationId.value;
    if (!conversationId || !asset?.id) {
        return;
    }

    assetActionLoadingId.value = asset.id;
    try {
        const data = await apiRequest(
            `/api/v1/chat/history/${conversationId}/assets/${asset.id}/signed-url?expires_minutes=10`
        );
        const url = String(data.signed_url ?? '');
        if (!url) {
            throw new Error('Unable to generate secure file URL.');
        }

        if (forceDownload) {
            const link = document.createElement('a');
            link.href = url;
            link.download = asset.name || 'download';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            window.open(url, '_blank', 'noopener,noreferrer');
        }
    } catch (error) {
        chatErrorMessage.value = error.message || 'Failed to open uploaded asset.';
        showErrorAlert(chatErrorMessage.value, 'Asset open failed');
    } finally {
        assetActionLoadingId.value = null;
    }
}

function toggleMic() {
    if (isSharedView.value) {
        return;
    }

    if (!speechSupported) {
        chatErrorMessage.value = 'Voice input is not supported in this browser.';
        showErrorAlert(chatErrorMessage.value, 'Microphone unavailable');
        return;
    }

    if (listening.value) {
        recognition?.stop();
        return;
    }

    if (!recognition) {
        recognition = new SpeechRecognitionCtor();
        recognition.lang = 'en-US';
        recognition.continuous = true;
        recognition.interimResults = true;

        recognition.onresult = (event) => {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            inputMessage.value = transcript.trim();
        };

        recognition.onerror = (event) => {
            const code = String(event?.error || '').toLowerCase();
            if (code === 'not-allowed' || code === 'service-not-allowed') {
                chatErrorMessage.value = 'Microphone permission is blocked. Please allow mic access in your browser/site settings and try again.';
            } else if (code === 'audio-capture') {
                chatErrorMessage.value = 'No microphone was found. Please connect a microphone and try again.';
            } else {
                chatErrorMessage.value = 'Microphone error. Please check mic permission.';
            }
            showErrorAlert(chatErrorMessage.value, 'Microphone error');
            listening.value = false;
        };
        recognition.onend = () => {
            listening.value = false;
        };
    }

    chatErrorMessage.value = '';
    try {
        recognition.start();
        listening.value = true;
    } catch {
        chatErrorMessage.value = 'Unable to start microphone. Please allow mic permission and try again.';
        showErrorAlert(chatErrorMessage.value, 'Microphone start failed');
        listening.value = false;
    }
}

async function shareConversation() {
    if (!currentConversationId.value || shareLoading.value || isSharedView.value) {
        return;
    }

    shareLoading.value = true;
    try {
        const data = await apiRequest(`/api/v1/chat/history/${currentConversationId.value}/share`, {
            method: 'POST',
        });
        const shareUrl = String(data.share_url ?? '');
        sharedUrlText.value = shareUrl;
        shareCopiedText.value = 'Copy';
        shareModalTitle.value = String(
            data.conversation?.subject
            || currentConversationSubject.value
            || 'Shared conversation'
        );
        if (shareUrl !== '') {
            shareModalOpen.value = true;
            statusText.value = 'Share link created';
        }
    } catch (error) {
        const message = error?.message || 'Failed to create share link.';
        statusText.value = message;
        showErrorAlert(message, 'Share failed');
    } finally {
        shareLoading.value = false;
    }
}

function closeShareModal() {
    shareModalOpen.value = false;
}

async function copyShareLink() {
    const shareUrl = String(sharedUrlText.value || '').trim();
    if (shareUrl === '') {
        return;
    }

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(shareUrl);
        } else {
            throw new Error('Clipboard unavailable');
        }
        shareCopiedText.value = 'Copied';
        statusText.value = 'Share link copied';
    } catch {
        showErrorAlert('Unable to copy automatically. Please copy the link manually.', 'Copy failed');
    }
}

function shareOnPlatform(platform) {
    const shareUrl = encodeURIComponent(String(sharedUrlText.value || '').trim());
    const shareTitle = encodeURIComponent(String(shareModalTitle.value || 'Shared conversation'));
    if (shareUrl === '') {
        return;
    }

    const target = platform === 'x'
        ? `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareTitle}`
        : platform === 'linkedin'
            ? `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`
            : `https://www.reddit.com/submit?url=${shareUrl}&title=${shareTitle}`;

    window.open(target, '_blank', 'noopener,noreferrer');
}

async function loadSharedConversation(shareToken) {
    const token = String(shareToken ?? '').trim();
    if (token === '') {
        messages.value = [];
        statusText.value = 'Shared conversation not found';
        return;
    }

    sending.value = false;
    searchModalOpen.value = false;
    currentConversationId.value = null;
    conversations.value = [];
    conversationAssets.value = [];
    uploadsModalOpen.value = false;
    attachments.value = [];
    statusText.value = 'Loading shared conversation...';
    chatErrorMessage.value = '';
    modelErrorMessage.value = '';
    try {
        const data = await apiRequest(`/api/v1/public/chat/share/${encodeURIComponent(token)}?limit=200`, {
            credentials: 'omit',
            headers: {
                Accept: 'application/json',
            },
        });
        messages.value = (data.messages ?? []).map((item) => ({
            role: item.role,
            content: item.content,
            attachments: [],
            processing: false,
        }));
        if (data.conversation?.model) {
            model.value = String(data.conversation.model);
        }
        statusText.value = 'Shared conversation loaded';
        await scrollMessagesToBottom();
    } catch (error) {
        messages.value = [];
        statusText.value = error?.message || 'Unable to load shared conversation.';
        showErrorAlert(statusText.value, 'Shared chat unavailable');
    }
}

async function loadConversationList() {
    await loadConversationListInternal(true);
}

async function loadUsage() {
    usage.value = await apiRequest('/api/v1/usage');
}

async function loadConversationListInternal(reset = false) {
    if (historyLoading.value) {
        return;
    }

    if (reset) {
        historyPage.value = 1;
        historyHasMore.value = true;
    }

    if (!historyHasMore.value && !reset) {
        return;
    }

    historyLoading.value = true;
    try {
        const pageToLoad = historyPage.value;
        const data = await apiRequest(`/api/v1/chat/histories?limit=30&page=${pageToLoad}`);
        const incoming = data.conversations ?? [];

        if (reset) {
            conversations.value = incoming;
        } else {
            const existingIds = new Set(conversations.value.map((item) => item.id));
            const uniqueIncoming = incoming.filter((item) => !existingIds.has(item.id));
            conversations.value = [...conversations.value, ...uniqueIncoming];
        }

        const total = Number(data.total ?? 0);
        historyHasMore.value = conversations.value.length < total;
        historyPage.value = pageToLoad + 1;
    } catch (error) {
        statusText.value = error.message || 'Failed to load conversation history';
        showErrorAlert(statusText.value, 'Conversation history failed');
    } finally {
        historyLoading.value = false;
    }
}

function handleHistoryScroll(event) {
    const target = event.target;
    if (!target || historyLoading.value || !historyHasMore.value) {
        return;
    }

    const threshold = 80;
    const distanceFromBottom = target.scrollHeight - target.scrollTop - target.clientHeight;
    if (distanceFromBottom <= threshold) {
        loadConversationListInternal(false);
    }
}

watch(searchQuery, (value) => {
    if (isSharedView.value) {
        return;
    }

    if (!searchModalOpen.value) {
        return;
    }

    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    const trimmed = String(value ?? '').trim();
    if (trimmed === '') {
        searchResults.value = conversations.value;
        searchLoading.value = false;
        searchError.value = '';
        return;
    }

    searchDebounceTimer = setTimeout(() => {
        runSearchFromDatabase(trimmed);
    }, 250);
});

onBeforeUnmount(() => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    if (recognition && listening.value) {
        recognition.stop();
    }
});

onMounted(() => {
    if (isSharedView.value) {
        loadSharedConversation(shareTokenFromRoute.value);
        return;
    }

    loadBootstrapData().then(async () => {
        const conversationIdFromRoute = parseConversationId(route.params.conversationId);
        if (conversationIdFromRoute !== null) {
            await openConversation(conversationIdFromRoute, false);
        }
    });
});

watch(
    () => route.params.conversationId,
    async (value) => {
        if (isSharedView.value) {
            return;
        }

        const conversationIdFromRoute = parseConversationId(value);
        if (conversationIdFromRoute === null) {
            if (currentConversationId.value !== null || messages.value.length > 0) {
                currentConversationId.value = null;
                messages.value = [];
                conversationAssets.value = [];
                statusText.value = 'Ready';
            }
            return;
        }

        if (currentConversationId.value === conversationIdFromRoute) {
            return;
        }

        await openConversation(conversationIdFromRoute, false);
    }
);

watch(
    () => route.params.shareToken,
    async (value) => {
        if (!isSharedView.value) {
            return;
        }
        await loadSharedConversation(value);
    }
);
</script>
