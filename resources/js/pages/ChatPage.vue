<template>
    <div class="grid min-h-[82vh] gap-0 overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 md:grid-cols-[280px_1fr]">
        <aside class="flex flex-col border-r border-zinc-800 bg-zinc-900/70">
            <div class="space-y-2 border-b border-zinc-800 p-3">
                <button
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-left text-sm font-semibold text-zinc-100 hover:bg-zinc-700"
                    @click="startNewChat"
                >
                    + New chat
                </button>
                <button
                    class="w-full rounded-lg px-3 py-2 text-left text-sm text-zinc-300 hover:bg-zinc-800/60"
                    type="button"
                    @click="openSearchModal"
                >
                    Search chats
                </button>
                <button
                    class="w-full rounded-lg px-3 py-2 text-left text-sm text-zinc-300 hover:bg-zinc-800/60"
                    type="button"
                >
                    Explore GPTs
                </button>
            </div>

            <div class="flex-1 space-y-1 overflow-auto p-2" @scroll="handleHistoryScroll">
                <p class="px-2 py-1 text-[11px] uppercase tracking-wide text-zinc-500">Recent</p>
                <button
                    v-for="conversation in conversations"
                    :key="conversation.id"
                    class="w-full rounded-lg px-3 py-2 text-left transition"
                    :class="currentConversationId === conversation.id
                        ? 'bg-zinc-800 text-white'
                        : 'text-zinc-300 hover:bg-zinc-800/60'"
                    @click="openConversation(conversation.id)"
                >
                    <p class="truncate text-sm font-medium">{{ conversation.subject || 'Untitled' }}</p>
                    <p class="mt-1 truncate text-xs text-zinc-500">{{ conversation.last_assistant_message || 'No reply yet' }}</p>
                </button>
                <p v-if="!conversations.length" class="px-3 py-2 text-xs text-zinc-500">No conversations yet.</p>
                <p v-if="historyLoading" class="px-3 py-2 text-xs text-zinc-500">Loading history...</p>
                <p v-else-if="!historyHasMore && conversations.length" class="px-3 py-2 text-xs text-zinc-600">
                    No more chats
                </p>
            </div>
        </aside>

        <section class="relative flex min-h-[82vh] flex-col bg-zinc-950">
            <div class="flex items-center gap-2 border-b border-zinc-800 px-4 py-3">
                <select
                    v-model="model"
                    class="max-w-[280px] rounded-md border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500"
                >
                    <option v-for="item in modelOptions" :key="item.model" :value="item.model">
                        {{ item.display_name }}
                    </option>
                </select>
                <select
                    v-model="capabilityFilter"
                    class="rounded-md border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500"
                >
                    <option value="all">All</option>
                    <option value="vision">Vision</option>
                    <option value="reasoning">Reasoning</option>
                    <option value="web_search">Web Search</option>
                    <option value="tools">Tools</option>
                </select>
                <div class="ml-auto text-xs text-zinc-500">
                    {{ usage.total_tokens ?? 0 }} / {{ usage.token_limit ?? 10000 }} tokens
                </div>
            </div>

            <div class="flex-1 overflow-auto px-4 py-5">
                <div v-if="messages.length" class="mx-auto w-full max-w-3xl space-y-4">
                    <div
                        v-for="(message, index) in messages"
                        :key="`${message.role}-${index}`"
                        class="rounded-xl px-4 py-3 text-sm"
                        :class="message.role === 'user'
                            ? 'ml-auto max-w-[85%] bg-zinc-200 text-zinc-900'
                            : 'max-w-[92%] bg-zinc-900 text-zinc-100'"
                    >
                        <p class="mb-1 text-[10px] uppercase tracking-wide opacity-70">{{ message.role }}</p>
                        <p class="whitespace-pre-wrap">{{ message.content }}</p>
                    </div>
                </div>

                <div v-else class="flex h-full items-center justify-center">
                    <div class="w-full max-w-2xl text-center">
                        <p class="mb-6 text-3xl font-medium text-zinc-200">Where should we begin?</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-800 bg-zinc-950/95 p-4">
                <div class="mx-auto w-full max-w-3xl">
                    <div class="rounded-2xl border border-zinc-700 bg-zinc-900 px-3 py-2">
                        <textarea
                            v-model="inputMessage"
                            rows="2"
                            class="w-full resize-none bg-transparent text-sm text-zinc-100 outline-none"
                            placeholder="Ask anything..."
                        />
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-zinc-500">{{ statusText }}</p>
                            <button
                                class="rounded-full bg-zinc-100 px-4 py-1.5 text-xs font-semibold text-zinc-900 hover:bg-white disabled:opacity-60"
                                :disabled="sending || !inputMessage.trim()"
                                @click="sendMessage"
                            >
                                {{ sending ? 'Sending...' : 'Send' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="searchModalOpen"
            class="fixed inset-0 z-40 flex items-start justify-center bg-black/70 px-4 pt-20"
            @click.self="closeSearchModal"
        >
            <div class="w-full max-w-2xl rounded-xl border border-zinc-700 bg-zinc-900 shadow-2xl">
                <div class="border-b border-zinc-700 p-4">
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200 outline-none focus:border-zinc-500"
                        placeholder="Search chat history..."
                        @keydown.esc="closeSearchModal"
                    />
                </div>
                <div class="max-h-[60vh] overflow-auto p-2">
                    <p v-if="searchLoading" class="px-3 py-3 text-sm text-zinc-500">Searching...</p>
                    <p v-else-if="searchError" class="px-3 py-3 text-sm text-red-400">{{ searchError }}</p>
                    <button
                        v-for="conversation in filteredConversations"
                        :key="`search-${conversation.id}`"
                        class="w-full rounded-lg px-3 py-2 text-left text-zinc-300 hover:bg-zinc-800/60"
                        @click="openConversationFromSearch(conversation.id)"
                    >
                        <p class="truncate text-sm font-medium text-zinc-100">{{ conversation.subject || 'Untitled' }}</p>
                        <p class="mt-1 truncate text-xs text-zinc-500">{{ conversation.last_assistant_message || 'No reply yet' }}</p>
                    </button>
                    <p v-if="!searchLoading && !searchError && !searchQuery.trim()" class="px-3 py-3 text-sm text-zinc-500">
                        Type to search chats from database.
                    </p>
                    <p v-else-if="!searchLoading && !searchError && searchQuery.trim() && !filteredConversations.length" class="px-3 py-3 text-sm text-zinc-500">
                        No matching chats found.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

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
const model = ref('gpt-4o-mini');
const temperature = ref(0.7);
const maxTokens = ref(512);
const capabilityFilter = ref('all');
const inputMessage = ref('');
const sending = ref(false);
const statusText = ref('Ready');
const historyLoading = ref(false);
const historyPage = ref(1);
const historyHasMore = ref(true);

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
    return searchResults.value;
});

watch([capabilityFilter, models], () => {
    if (!modelOptions.value.some((item) => item.model === model.value)) {
        model.value = modelOptions.value[0]?.model ?? '';
    }
});

const apiHeaders = computed(() => {
    return { Accept: 'application/json', 'Content-Type': 'application/json' };
});

async function apiRequest(path, options = {}) {
    const response = await fetch(path, {
        credentials: 'include',
        ...options,
        headers: {
            ...apiHeaders.value,
            ...(options.headers ?? {}),
        },
    });

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || `Request failed: ${response.status}`);
    }

    return response.json();
}

async function loadBootstrapData() {
    statusText.value = 'Loading...';
    try {
        const [usageData, modelData] = await Promise.all([
            apiRequest('/api/v1/usage'),
            apiRequest('/api/v1/models'),
        ]);

        usage.value = usageData ?? { total_tokens: 0, recent_requests: [] };
        models.value = modelData.models ?? [];
        await loadConversationList(true);
        const first = modelOptions.value[0];
        if (first) {
            model.value = first.model;
        }

        statusText.value = 'Ready';
    } catch (error) {
        statusText.value = error.message || 'Failed to load data';
    }
}

function parseConversationId(value) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    if (!Number.isFinite(parsed) || parsed <= 0) {
        return null;
    }

    return parsed;
}

async function syncConversationRoute(conversationId) {
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

    await router.push({
        name: 'chat.conversation',
        params: { conversationId: String(parsed) },
    });
}

async function openConversation(conversationId, syncRoute = true) {
    const parsedConversationId = parseConversationId(conversationId);
    if (parsedConversationId === null) {
        return;
    }

    currentConversationId.value = parsedConversationId;
    statusText.value = 'Loading conversation...';
    try {
        const data = await apiRequest(`/api/v1/chat/history/${parsedConversationId}?limit=200`);
        messages.value = (data.messages ?? []).map((item) => ({
            role: item.role,
            content: item.content,
        }));
        if (syncRoute) {
            await syncConversationRoute(parsedConversationId);
        }
        statusText.value = 'Conversation loaded';
    } catch (error) {
        statusText.value = error.message || 'Failed to load conversation';
    }
}

async function startNewChat() {
    currentConversationId.value = null;
    messages.value = [];
    inputMessage.value = '';
    await syncConversationRoute(null);
    statusText.value = 'New chat started';
}

function openSearchModal() {
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
        searchResults.value = [];
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
    } finally {
        if (requestId === activeSearchRequestId) {
            searchLoading.value = false;
        }
    }
}

async function sendMessage() {
    const text = inputMessage.value.trim();
    if (!text || sending.value) {
        return;
    }

    const nextMessages = [...messages.value, { role: 'user', content: text }];
    messages.value = nextMessages;
    inputMessage.value = '';
    sending.value = true;
    statusText.value = 'Sending...';

    try {
        const payload = {
            model: model.value,
            conversation_id: currentConversationId.value ?? undefined,
            save_history: true,
            stream: false,
            temperature: Number(temperature.value),
            max_tokens: Number(maxTokens.value),
            messages: nextMessages,
        };

        const data = await apiRequest('/api/v1/chat', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        if (data.conversation_id) {
            currentConversationId.value = data.conversation_id;
            await syncConversationRoute(data.conversation_id);
        }

        messages.value = [...nextMessages, { role: 'assistant', content: data.message ?? '' }];
        await Promise.all([loadConversationList(), loadUsage()]);
        statusText.value = 'Response received';
    } catch (error) {
        statusText.value = error.message || 'Failed to send message';
    } finally {
        sending.value = false;
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
    if (!searchModalOpen.value) {
        return;
    }

    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    const trimmed = String(value ?? '').trim();
    if (trimmed === '') {
        searchResults.value = [];
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
});

onMounted(() => {
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
        const conversationIdFromRoute = parseConversationId(value);
        if (conversationIdFromRoute === null) {
            if (currentConversationId.value !== null || messages.value.length > 0) {
                currentConversationId.value = null;
                messages.value = [];
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
</script>
