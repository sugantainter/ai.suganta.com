<template>
    <div class="shrink-0 border-b border-zinc-800 px-4 py-3">
        <div v-if="isSharedView" class="flex items-center justify-between gap-2">
            <div>
                <p class="text-sm font-semibold text-zinc-100">Shared conversation</p>
                <p class="text-xs text-zinc-500">View only mode</p>
            </div>
            <a
                href="/"
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
            >
                Login
            </a>
        </div>

        <div v-else class="flex flex-wrap items-center gap-2">
            <button
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-xs text-zinc-200 md:hidden"
                @click="$emit('open-search')"
            >
                Search
            </button>
            <select
                :value="modelValue"
                :disabled="modelOptions.length === 0"
                class="max-w-[48vw] rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500 sm:max-w-[260px] max-[420px]:w-full max-[420px]:max-w-none"
                @change="$emit('update:modelValue', $event.target.value)"
            >
                <option v-for="item in modelOptions" :key="item.model" :value="item.model">
                    {{ item.display_name }}
                </option>
            </select>
            <select
                :value="capabilityFilter"
                class="max-w-[38vw] rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500 sm:max-w-none max-[420px]:w-full max-[420px]:max-w-none"
                @change="$emit('update:capabilityFilter', $event.target.value)"
            >
                <option value="all">All</option>
                <option value="vision">Vision</option>
                <option value="reasoning">Reasoning</option>
                <option value="web_search">Web search</option>
                <option value="tools">Tools</option>
            </select>
            <button
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800 disabled:opacity-60 max-[420px]:w-full"
                :disabled="shareLoading || !canShare"
                @click="$emit('share')"
            >
                {{ shareLoading ? 'Sharing...' : 'Share' }}
            </button>
            <p class="w-full text-right text-xs text-zinc-500 sm:ml-auto sm:w-auto">{{ statusText }}</p>
        </div>

        <p v-if="modelErrorMessage" class="mt-2 text-xs text-red-400">
            {{ modelErrorMessage }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    isSharedView: { type: Boolean, default: false },
    modelValue: { type: String, default: '' },
    capabilityFilter: { type: String, default: 'all' },
    modelOptions: { type: Array, default: () => [] },
    statusText: { type: String, default: 'Ready' },
    modelErrorMessage: { type: String, default: '' },
    shareLoading: { type: Boolean, default: false },
    canShare: { type: Boolean, default: false },
});

defineEmits([
    'update:modelValue',
    'update:capabilityFilter',
    'open-search',
    'share',
]);
</script>
