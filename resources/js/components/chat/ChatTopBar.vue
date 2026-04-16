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

        <div v-else class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
            <button
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-xs text-zinc-200 md:hidden"
                @click="$emit('open-search')"
            >
                Search
            </button>
            <select
                :value="modelValue"
                :disabled="modelOptions.length === 0 || compareMode"
                class="min-w-[180px] rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500 max-[420px]:w-full"
                @change="$emit('update:modelValue', $event.target.value)"
            >
                <option v-for="item in modelOptions" :key="item.model" :value="item.model">
                    {{ item.display_name }}
                </option>
            </select>
            <button
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
                type="button"
                @click="$emit('update:compareMode', !compareMode)"
            >
                {{ compareMode ? 'Compare: ON' : 'Compare: OFF' }}
            </button>
            <select
                :value="capabilityFilter"
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500 max-[420px]:w-full"
                @change="$emit('update:capabilityFilter', $event.target.value)"
            >
                <option value="all">All</option>
                <option value="vision">Vision</option>
                <option value="reasoning">Reasoning</option>
                <option value="web_search">Web search</option>
                <option value="tools">Tools</option>
            </select>
            <select
                :value="responseStyle"
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500 max-[420px]:w-full"
                @change="$emit('update:responseStyle', $event.target.value)"
            >
                <option value="concise">Concise</option>
                <option value="balanced">Balanced</option>
                <option value="detailed">Detailed</option>
            </select>
            <button
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800 disabled:opacity-60 max-[420px]:w-full"
                :disabled="shareLoading || !canShare"
                @click="$emit('share')"
            >
                {{ shareLoading ? 'Sharing...' : 'Share' }}
            </button>
            <p class="w-full text-left text-xs text-zinc-500 sm:ml-auto sm:w-auto sm:text-right">{{ statusText }}</p>
            </div>

            <div v-if="compareMode" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-xs font-medium text-zinc-200">Select models to compare</p>
                    <button
                        type="button"
                        class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                        :disabled="selectedCompareModels.length === 0"
                        @click="$emit('update:compareModels', [])"
                    >
                        Clear
                    </button>
                </div>

                <div class="max-h-36 space-y-1 overflow-y-auto pr-1">
                    <label
                        v-for="item in modelOptions"
                        :key="`compare-picker-${item.model}`"
                        class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-xs text-zinc-300 hover:bg-zinc-800/80"
                    >
                        <input
                            type="checkbox"
                            class="h-3.5 w-3.5 rounded border-zinc-600 bg-zinc-900 text-blue-500 focus:ring-0"
                            :checked="selectedCompareModels.includes(item.model)"
                            @change="toggleCompareModel(item.model)"
                        >
                        <span class="truncate">{{ item.display_name }}</span>
                    </label>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span
                        v-for="item in selectedCompareDisplayModels"
                        :key="`selected-compare-${item.model}`"
                        class="inline-flex items-center gap-1 rounded-full border border-blue-500/40 bg-blue-500/10 px-2 py-0.5 text-[11px] text-blue-200"
                    >
                        <span class="max-w-[140px] truncate">{{ item.display_name }}</span>
                        <button
                            type="button"
                            class="rounded-full px-1 text-blue-200 hover:bg-blue-500/20"
                            @click="removeCompareModel(item.model)"
                        >
                            x
                        </button>
                    </span>
                    <span v-if="selectedCompareDisplayModels.length === 0" class="text-[11px] text-zinc-500">
                        No models selected yet.
                    </span>
                </div>
            </div>
        </div>

        <p v-if="modelErrorMessage" class="mt-2 text-xs text-red-400">
            {{ modelErrorMessage }}
        </p>
    </div>
</template>

<script setup>
const props = defineProps({
    isSharedView: { type: Boolean, default: false },
    modelValue: { type: String, default: '' },
    compareMode: { type: Boolean, default: false },
    compareModels: { type: Array, default: () => [] },
    capabilityFilter: { type: String, default: 'all' },
    responseStyle: { type: String, default: 'balanced' },
    modelOptions: { type: Array, default: () => [] },
    statusText: { type: String, default: 'Ready' },
    modelErrorMessage: { type: String, default: '' },
    shareLoading: { type: Boolean, default: false },
    canShare: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:modelValue',
    'update:compareMode',
    'update:compareModels',
    'update:capabilityFilter',
    'update:responseStyle',
    'open-search',
    'share',
]);

const selectedCompareModels = computed(() => (
    Array.isArray(props.compareModels)
        ? props.compareModels.map((item) => String(item || '')).filter((item) => item !== '')
        : []
));

const selectedCompareDisplayModels = computed(() => {
    const optionMap = new Map(
        (Array.isArray(props.modelOptions) ? props.modelOptions : []).map((item) => [String(item.model || ''), item])
    );

    return selectedCompareModels.value
        .map((modelKey) => optionMap.get(modelKey))
        .filter((item) => item && String(item.model || '') !== '');
});

function toggleCompareModel(modelKey) {
    const normalized = String(modelKey || '');
    if (normalized === '') {
        return;
    }

    const next = [...selectedCompareModels.value];
    const existingIndex = next.indexOf(normalized);
    if (existingIndex >= 0) {
        next.splice(existingIndex, 1);
    } else {
        next.push(normalized);
    }

    emit('update:compareModels', next);
}

function removeCompareModel(modelKey) {
    const normalized = String(modelKey || '');
    if (normalized === '') {
        return;
    }

    emit('update:compareModels', selectedCompareModels.value.filter((item) => item !== normalized));
}
</script>
