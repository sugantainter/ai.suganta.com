<template>
    <div class="grid min-h-[80vh] gap-4 md:grid-cols-[320px_1fr]">
        <aside class="rounded-2xl border border-zinc-800/80 bg-linear-to-b from-zinc-900/95 to-zinc-950/95 shadow-xl shadow-black/30">
            <div class="space-y-3 border-b border-zinc-800 p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold tracking-wide text-zinc-200">Conversations</h2>
                    <button
                        class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-900 hover:bg-white"
                        @click="startNewChat"
                    >
                        New Chat
                    </button>
                </div>
                <div class="space-y-2">
                    <label class="text-xs text-zinc-400">Optional API Key</label>
                    <input
                        v-model="apiKey"
                        type="password"
                        class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                        placeholder="x-api-key (optional)"
                    />
                </div>
            </div>

            <div class="max-h-[60vh] space-y-2 overflow-auto p-3">
                <button
                    v-for="conversation in conversations"
                    :key="conversation.id"
                    class="w-full rounded-lg border px-3 py-2 text-left transition"
                    :class="currentConversationId === conversation.id
                        ? 'border-zinc-500 bg-zinc-800'
                        : 'border-zinc-800 bg-zinc-900 hover:border-zinc-700'"
                    @click="openConversation(conversation.id)"
                >
                    <p class="truncate text-sm font-medium text-zinc-100">{{ conversation.subject || 'Untitled' }}</p>
                    <p class="mt-1 line-clamp-2 text-xs text-zinc-400">{{ conversation.last_assistant_message || 'No reply yet' }}</p>
                </button>

                <p v-if="!conversations.length" class="px-1 py-2 text-xs text-zinc-500">
                    No conversations found.
                </p>
            </div>
        </aside>

        <section class="flex min-h-[80vh] flex-col rounded-2xl border border-zinc-800/80 bg-linear-to-b from-zinc-900/90 to-zinc-950/90 shadow-xl shadow-black/30">
            <div class="grid gap-3 border-b border-zinc-800 p-4 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs text-zinc-400">Model</label>
                    <select
                        v-model="model"
                        class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    >
                        <option v-for="item in modelOptions" :key="item.model" :value="item.model">
                            {{ item.display_name }} ({{ item.provider }})
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-zinc-400">Capability Filter</label>
                    <select
                        v-model="capabilityFilter"
                        class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    >
                        <option value="all">All</option>
                        <option value="vision">Vision</option>
                        <option value="reasoning">Reasoning</option>
                        <option value="web_search">Web Search</option>
                        <option value="tools">Tools</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-zinc-400">Temperature</label>
                    <input
                        v-model.number="temperature"
                        type="number"
                        step="0.1"
                        min="0"
                        max="2"
                        class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs text-zinc-400">Max Tokens</label>
                    <input
                        v-model.number="maxTokens"
                        type="number"
                        min="1"
                        max="8192"
                        class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    />
                </div>
            </div>

            <div class="grid flex-1 gap-4 p-4 md:grid-cols-[1fr_280px]">
                <div class="flex min-h-0 flex-col rounded-lg border border-zinc-800 bg-zinc-950">
                    <div class="flex-1 space-y-3 overflow-auto p-4">
                        <div
                            v-for="(message, index) in messages"
                            :key="`${message.role}-${index}`"
                            class="max-w-[90%] rounded-lg px-3 py-2 text-sm"
                            :class="message.role === 'user'
                                ? 'ml-auto bg-zinc-200 text-zinc-900'
                                : 'bg-zinc-800 text-zinc-100'"
                        >
                            <p class="mb-1 text-[10px] uppercase tracking-wide opacity-70">{{ message.role }}</p>
                            <p class="whitespace-pre-wrap">{{ message.content }}</p>
                        </div>

                        <p v-if="!messages.length" class="text-sm text-zinc-500">
                            Start a new message to begin chatting.
                        </p>
                    </div>

                    <div class="space-y-2 border-t border-zinc-800 p-3">
                        <textarea
                            v-model="inputMessage"
                            rows="3"
                            class="w-full resize-none rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                            placeholder="Ask anything..."
                        />
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-zinc-500">{{ statusText }}</p>
                            <button
                                class="rounded-md bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-900 hover:bg-white disabled:opacity-60"
                                :disabled="sending || !inputMessage.trim()"
                                @click="sendMessage"
                            >
                                {{ sending ? 'Sending...' : 'Send' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 rounded-lg border border-zinc-800 bg-zinc-950 p-3">
                    <h3 class="text-sm font-semibold text-zinc-200">Usage</h3>
                    <p class="text-xs text-zinc-400">Total Tokens</p>
                    <p class="text-2xl font-semibold text-white">{{ usage.total_tokens ?? 0 }}</p>
                    <p class="text-xs text-zinc-400">Limit: {{ usage.token_limit ?? 10000 }}</p>
                    <p class="text-xs text-zinc-400">Remaining: {{ usage.remaining_tokens ?? 10000 }}</p>
                    <div class="space-y-2 pt-2">
                        <h4 class="text-xs uppercase tracking-wide text-zinc-500">Recent Requests</h4>
                        <div
                            v-for="item in usage.recent_requests || []"
                            :key="item.id"
                            class="rounded-md border border-zinc-800 bg-zinc-900 p-2"
                        >
                            <p class="text-xs text-zinc-300">{{ item.provider }} / {{ item.model }}</p>
                            <p class="text-[11px] text-zinc-500">{{ item.total_tokens }} tokens · {{ item.status }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const conversations = ref([]);
const messages = ref([]);
const usage = ref({ total_tokens: 0, recent_requests: [] });
const models = ref([]);
const apiKey = ref('');

const currentConversationId = ref(null);
const model = ref('gpt-4o-mini');
const temperature = ref(0.7);
const maxTokens = ref(512);
const capabilityFilter = ref('all');
const inputMessage = ref('');
const sending = ref(false);
const statusText = ref('Ready');

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

watch([capabilityFilter, models], () => {
    if (!modelOptions.value.some((item) => item.model === model.value)) {
        model.value = modelOptions.value[0]?.model ?? '';
    }
});

const apiHeaders = computed(() => {
    const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
    if (apiKey.value.trim()) {
        headers['X-API-Key'] = apiKey.value.trim();
    }
    return headers;
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
        const [historyData, usageData, modelData] = await Promise.all([
            apiRequest('/api/v1/chat/histories?limit=30&page=1'),
            apiRequest('/api/v1/usage'),
            apiRequest('/api/v1/models'),
        ]);

        conversations.value = historyData.conversations ?? [];
        usage.value = usageData ?? { total_tokens: 0, recent_requests: [] };
        models.value = modelData.models ?? [];
        const first = modelOptions.value[0];
        if (first) {
            model.value = first.model;
        }

        statusText.value = 'Ready';
    } catch (error) {
        statusText.value = error.message || 'Failed to load data';
    }
}

async function openConversation(conversationId) {
    currentConversationId.value = conversationId;
    statusText.value = 'Loading conversation...';
    try {
        const data = await apiRequest(`/api/v1/chat/history/${conversationId}?limit=200`);
        messages.value = (data.messages ?? []).map((item) => ({
            role: item.role,
            content: item.content,
        }));
        statusText.value = 'Conversation loaded';
    } catch (error) {
        statusText.value = error.message || 'Failed to load conversation';
    }
}

function startNewChat() {
    currentConversationId.value = null;
    messages.value = [];
    inputMessage.value = '';
    statusText.value = 'New chat started';
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
    const data = await apiRequest('/api/v1/chat/histories?limit=30&page=1');
    conversations.value = data.conversations ?? [];
}

async function loadUsage() {
    usage.value = await apiRequest('/api/v1/usage');
}

onMounted(() => {
    loadBootstrapData();
});
</script>
