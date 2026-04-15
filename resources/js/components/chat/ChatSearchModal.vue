<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/70 px-4 pt-20"
        @click.self="$emit('close')"
    >
        <div class="w-full max-w-2xl rounded-2xl border border-zinc-700 bg-[#1f1f1f] shadow-2xl">
            <div class="border-b border-zinc-700 p-4">
                <input
                    :value="query"
                    type="text"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200 outline-none focus:border-zinc-500"
                    placeholder="Search chat history..."
                    @keydown.esc="$emit('close')"
                    @input="$emit('update:query', $event.target.value)"
                />
            </div>
            <div class="max-h-[60vh] overflow-auto p-2">
                <p v-if="loading" class="px-3 py-3 text-sm text-zinc-500">Searching...</p>
                <p v-else-if="error" class="px-3 py-3 text-sm text-red-400">{{ error }}</p>
                <button
                    v-for="conversation in conversations"
                    :key="`search-${conversation.id}`"
                    class="mb-1 w-full rounded-lg px-3 py-2 text-left text-zinc-300 hover:bg-zinc-800/60"
                    @click="$emit('open-conversation', conversation.id)"
                >
                    <p class="truncate text-sm font-medium text-zinc-100">{{ conversation.subject || 'Untitled chat' }}</p>
                    <p class="mt-1 truncate text-xs text-zinc-500">{{ conversation.last_assistant_message || 'No reply yet' }}</p>
                </button>
                <p v-if="!loading && !error && !query.trim() && !conversations.length" class="px-3 py-3 text-sm text-zinc-500">
                    No previous chats found yet.
                </p>
                <p v-else-if="!loading && !error && !query.trim()" class="px-3 py-3 text-sm text-zinc-500">
                    Select any previous chat to continue the conversation.
                </p>
                <p v-else-if="!loading && !error && query.trim() && !conversations.length" class="px-3 py-3 text-sm text-zinc-500">
                    No matching chats found.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    open: { type: Boolean, default: false },
    query: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    conversations: { type: Array, default: () => [] },
});

defineEmits(['close', 'update:query', 'open-conversation']);
</script>
