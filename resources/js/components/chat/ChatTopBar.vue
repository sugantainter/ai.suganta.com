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
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-2.5">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-xs font-medium text-zinc-300">Chat Controls</p>
                    <p class="text-[11px] text-zinc-500">{{ statusText }}</p>
                </div>
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
                </div>
            </div>

            <div
                v-if="compareMode && !hideComparePicker"
                class="rounded-lg border border-zinc-800 bg-zinc-900/30 px-3 py-2"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-medium text-zinc-300">
                        Compare mode active
                    </p>
                    <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-2 py-0.5 text-[11px] text-blue-200">
                        {{ compareModelsCount }} model{{ compareModelsCount === 1 ? '' : 's' }} selected
                    </span>
                    <span
                        v-if="compareModelsCount < 2"
                        class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[11px] text-amber-200"
                    >
                        Select at least 2 models
                    </span>
                    <button
                        type="button"
                        class="ml-auto rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-[11px] text-zinc-200 hover:bg-zinc-800"
                        @click="comparePickerExpanded = !comparePickerExpanded"
                    >
                        {{ comparePickerExpanded ? 'Hide settings' : 'Configure models' }}
                    </button>
                </div>
            </div>

            <CompareModelPicker
                v-if="compareMode && !hideComparePicker && comparePickerExpanded"
                :compare-models="compareModels"
                :model-options="modelOptions"
                @update:compare-models="$emit('update:compareModels', $event)"
            />
            <div
                v-else-if="compareMode && hideComparePicker"
                class="flex flex-wrap items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900/30 px-3 py-2 text-xs text-zinc-400"
            >
                <span>Comparison settings are hidden after chat starts. Start a new chat to change compared models.</span>
                <button
                    type="button"
                    class="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-[11px] text-zinc-200 hover:bg-zinc-800"
                    @click="$emit('start-new-chat-reconfigure')"
                >
                    New chat and reconfigure compare
                </button>
            </div>
        </div>

        <p v-if="modelErrorMessage" class="mt-2 text-xs text-red-400">
            {{ modelErrorMessage }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import CompareModelPicker from './CompareModelPicker.vue';

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
    hideComparePicker: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:modelValue',
    'update:compareMode',
    'update:compareModels',
    'update:capabilityFilter',
    'update:responseStyle',
    'open-search',
    'share',
    'start-new-chat-reconfigure',
]);

const comparePickerExpanded = ref(false);
const compareModelsCount = computed(() => (
    Array.isArray(props.compareModels)
        ? props.compareModels.filter((item) => String(item || '').trim() !== '').length
        : 0
));

watch(() => props.compareMode, (enabled) => {
    if (!enabled) {
        comparePickerExpanded.value = false;
        return;
    }

    // Auto-open config when compare mode has less than 2 models selected.
    comparePickerExpanded.value = compareModelsCount.value < 2;
}, { immediate: true });
</script>
